<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace qtype_recordrtc;

/**
 * This class holds all the information about one placeholder in a question.
 *
 * @package   qtype_recordrtc
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class widget_info {
    /** @var string The internal name of this widget (used, for example, as part of the filename). */
    public $name;

    /** @var string Widget type. One of the qtype_recordrtc::MEDIA_TYPE_... constants. */
    public $type;

    /** @var int Maximum recording duration in seconds. */
    public $maxduration = \qtype_recordrtc::DEFAULT_TIMELIMIT;

    /** @var string The placehodler in the question text which should be replaced by this placeholder. */
    public $placeholder;

    /** @var string Feedback to show beside this widget once the question is answered. */
    public $feedback = '';

    /** @var int Format of $feedback. One of the FORMAT_... constants. */
    public $feedbackformat = FORMAT_HTML;

    /** @var int If this widget has feedback that came from the database, the corresponding question_answer.id. */
    public $answerid = 0;

    /**
     * Constructor.
     *
     * @param string $name The internal name of this widget (used, for example, as part of the filename).
     * @param string $type Widget type. One of the qtype_recordrtc::MEDIA_TYPE_... constants.
     * @param int|null $maxduration Maximum recording duration in seconds. (Defaults to DEFAULT_TIMELIMIT.)
     */
    public function __construct(string $name, string $type, int $maxduration = null) {
        $this->name = $name;
        $this->type = $type;
        if ($maxduration !== null) {
            $this->maxduration = $this->limit_max_duration($maxduration);
        }
        $this->placeholder = self::make_placeholder($this->name, $this->type, $this->maxduration);
    }

    /**
     * Helper, to ensure that the time limit for a widget is never more than the admin-set limit.
     *
     * @param int $maxduration the requested time limit.
     * @return int actual time limit to use, which will be the smaller of the requested one and the admin limit.
     */
    protected function limit_max_duration(int $maxduration): int {
        if ($this->type === \qtype_recordrtc::MEDIA_TYPE_AUDIO || $this->type === \qtype_recordrtc::MEDIA_TYPE_HIDDEN_AUDIO) {
            $limit = get_config('qtype_recordrtc', 'audiotimelimit');
        } else {
            $limit = get_config('qtype_recordrtc', 'videotimelimit');
        }
        return min($maxduration, $limit);
    }

    /**
     * Get the placeholder, wrapped in <span class="nolink">...</span>, to protect
     * from Moodle filters.
     *
     * @return string
     */
    public function get_protected_placeholder(): string {
        return \html_writer::span($this->placeholder, 'nolink');
    }

    /**
     * Return true, if widget has audio type (video type otherwise)
     *
     * @return bool
     */
    public function is_audio_type(): bool {
        return $this->type === \qtype_recordrtc::MEDIA_TYPE_AUDIO || $this->type === \qtype_recordrtc::MEDIA_TYPE_HIDDEN_AUDIO;
    }

    /**
     * Return true, if widget has type with hidden question
     *
     * @return bool
     */
    public function is_hidden_type(): bool {
        return $this->type === \qtype_recordrtc::MEDIA_TYPE_HIDDEN_AUDIO || $this->type === \qtype_recordrtc::MEDIA_TYPE_HIDDEN_VIDEO;
    }

    /**
     * Get playback class based on widget type
     *
     * @param string      $filename     the file name.
     * @param string|null $recordingurl if we are re-displaying, after a recording was made, this is the audio file.
     * @param bool        $candownload  whether the current user should see options to download the recordings.
     *
     * @return \qtype_recordrtc\output\playback_base|\qtype_recordrtc\output\audio_playback|\qtype_recordrtc\output\video_playback
     */
    public function get_playback(string $filename, ?string $recordingurl=null, bool $candownload=false): object {
        if ($this->is_audio_type()) {
            return new \qtype_recordrtc\output\audio_playback($filename, $recordingurl, $candownload);
        } else {
            return new \qtype_recordrtc\output\video_playback($filename, $recordingurl, $candownload);
        }
    }

    /**
     * Get recorder class based on widget type
     *
     * @param string           $filename     - the file name this recorder saves as.
     * @param bool             $allowpausing - whether the user is allowed to pause, mid-recording.
     * @param \moodle_url|null $recordingurl - if we are re-displaying, after a recording was made, this is the audio file.
     * @param bool             $candownload  - whether the current user should see options to download the recordings.
     * @param bool             $denyrerecord - whether the current user can re-record his answer
     *
     * @return \qtype_recordrtc\output\recorder_base|\qtype_recordrtc\output\audio_recorder|\qtype_recordrtc\output\video_recorder
     */
    public function get_recorder(string $filename, bool $allowpausing=false, ?\moodle_url $recordingurl=null,
        bool $candownload=false, bool $denyrerecord=false): object {
        if ($this->is_audio_type()) {
            return new \qtype_recordrtc\output\audio_recorder($this, $filename, $allowpausing, $recordingurl, $candownload, $denyrerecord);
        } else {
            return new \qtype_recordrtc\output\video_recorder($this, $filename, $allowpausing, $recordingurl, $candownload, $denyrerecord);
        }
    }

    /**
     * Create what the placeholder would need to be for a particular widget.
     *
     * @param string $name The internal name of this widget (used, for example, as part of the filename).
     * @param string $type Widget type. One of the qtype_recordrtc::MEDIA_TYPE_... constants.
     * @param int|null $maxduration Maximum recording duration in seconds. (Defaults to DEFAULT_TIMELIMIT.)
     * @return string the placeholder.
     */
    public static function make_placeholder(string $name, string $type, int $maxduration = null): string {
        if ($maxduration === null) {
            $maxduration = \qtype_recordrtc::DEFAULT_TIMELIMIT;
        }
        return '[[' . $name . ':' . $type . ':' . self::seconds_to_duration($maxduration) . ']]';
    }

    /**
     * Convert duration to an integer number of seconds.
     *
     * @param string $duration duration as it appears in a placeholder, e.g. '1m30s'.
     * @return int duration in seconds.
     */
    public static function duration_to_seconds(string $duration): int {
        $minutesandseconds = explode('m', $duration);
        // Only numbers followed by an 's'.
        if (count($minutesandseconds) === 1) {
            return trim($minutesandseconds[0], 's');
        }
        // Numbers followed by 'm', such as '1m', '2m'.
        if (!$minutesandseconds[1]) {
            return $minutesandseconds[0] * MINSECS;
        }
        // Numbers followed by 'm', followed by numbers and 's' such as '1m20s'.
        if (is_number($minutesandseconds[1])) {
            $seconds = $minutesandseconds[1];
        } else {
            $seconds = trim($minutesandseconds[1], 's');
        }
        return ($minutesandseconds[0] * MINSECS) + $seconds;
    }

    /**
     * Format a number of seconds as it needs to appear in placeholders.
     *
     * @param int $seconds duration in seconds.
     * @return string duration as it appears in a placeholder, e.g. '1m30s'.
     */
    public static function seconds_to_duration(int $seconds): string {
        $minutes = intdiv($seconds, MINSECS);
        $seconds -= $minutes * MINSECS;
        if ($seconds < 10) {
            $seconds = '0' . $seconds;
        }
        if ($minutes) {
            return $minutes . 'm' . $seconds . 's';
        } else {
            return $seconds . 's';
        }
    }
}
