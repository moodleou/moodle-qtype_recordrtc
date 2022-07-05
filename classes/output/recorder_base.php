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

namespace qtype_recordrtc\output;

use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Base class which holds the information which applies to both audio an video widgets.
 *
 * @package   qtype_recordrtc
 * @copyright 2022 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class recorder_base implements renderable, templatable {

    /**
     * @var string the file name this recorder saves as.
     */
    protected $filename;

    /**
     * @var int maximum allowed recording length, in seconds.
     */
    protected $maxrecordingduration;

    /**
     * @var bool whether the user is allowed to pause, mid-recording.
     */
    protected $allowpausing;

    /**
     * @var moodle_url|null if we are re-displaying, after a recording was made, this is the audio file.
     */
    protected $recordingurl;

    /**
     * @var bool whether the current user should see options to download the recordings.
     */
    protected $candownload;

    /**
     * Constructor.
     *
     * @param string $filename the file name this recorder saves as.
     * @param int $maxrecordingduration maximum allowed recording length, in seconds.
     * @param bool $allowpausing whether the user is allowed to pause, mid-recording.
     * @param moodle_url|null $recordingurl if we are re-displaying, after a recording was made, this is the audio file.
     * @param bool $candownload whether the current user should see options to download the recordings.
     */
    public function __construct(string $filename, int $maxrecordingduration,
            bool $allowpausing, ?moodle_url $recordingurl, bool $candownload) {
        $this->filename = $filename;
        $this->maxrecordingduration = $maxrecordingduration;
        $this->allowpausing = $allowpausing;
        $this->recordingurl = $recordingurl;
        $this->candownload = $candownload;
    }

    /**
     * Get the widget name, derived from the filename.
     *
     * @return string the widget name.
     */
    public function get_widget_name(): string {
        return str_replace('_', ' ', preg_replace('~\.[a-z0-9]*$~', '', $this->filename));
    }

    public function export_for_template(renderer_base $output): array {
        return [
            'filename' => $this->filename,
            'widgetname' => $this->get_widget_name(),
            'maxrecordingduration' => $this->maxrecordingduration,
            'allowpausing' => $this->allowpausing,
            'hasrecording' => $this->recordingurl !== null,
            'recordingurl' => $this->recordingurl ? $this->recordingurl->out(false) : '',
            'candownload' => $this->candownload,
        ];
    }
}
