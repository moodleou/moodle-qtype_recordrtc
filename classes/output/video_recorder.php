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

use renderer_base;

/**
 * Represents an audio widget, for output.
 *
 * @package   qtype_recordrtc
 * @copyright 2022 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class video_recorder extends recorder_base {
    /**
     * Helper to add 'width' and 'aspectratio' to the given context.
     *
     * @param array $context being built for the template.
     * @return array augmented context.
     */
    public static function add_width_and_aspect(array $context): array {
        [$videowidth, $videoheight] = explode(',', static::get_video_size());
        $context['width'] = $videowidth;
        if ($videowidth / $videoheight > 1.5) {
            $context['aspectratio'] = '16x9';
        } else {
            $context['aspectratio'] = '4x3';
        }

        return $context;
    }

    public function export_for_template(renderer_base $output): array {
        $context = parent::export_for_template($output);
        return self::add_width_and_aspect($context);
    }

    /**
     * Helper get video size config.
     *
     * @return string Video size config.
     */
    public static function get_video_size(): string {
        return get_config('qtype_recordrtc', 'videosize');
    }
}
