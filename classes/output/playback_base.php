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

use qtype_recordrtc;
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
abstract class playback_base implements renderable, templatable {

    /**
     * @var string the file name.
     */
    protected $filename;

    /**
     * @var string if we are re-displaying, after a recording was made, this is the audio file.
     */
    protected $recordingurl;

    /**
     * @var bool whether the current user should see options to download the recordings.
     */
    protected $candownload;

    /**
     * Constructor.
     *
     * @param string $filename the file name.
     * @param string|null $recordingurl if we are re-displaying, after a recording was made, this is the audio file.
     * @param bool $candownload whether the current user should see options to download the recordings.
     */
    public function __construct(string $filename, ?string $recordingurl, bool $candownload) {
        $this->filename = $filename;
        $this->recordingurl = $recordingurl;
        $this->candownload = $candownload;
    }

    public function export_for_template(renderer_base $output): array {
        return [
            'hasrecording' => $this->recordingurl !== null,
            'filename' => $this->filename,
            'recordingurl' => $this->recordingurl,
            'candownload' => $this->candownload,
        ];
    }
}
