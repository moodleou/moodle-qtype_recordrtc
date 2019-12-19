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
//

/**
 * Java script to make qtype recordrtc work.
 *
 * @package    qtype_recordrtc
 * @copyright  20119 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'qtype_recordrtc/avrecording'], function($, avrecording) {

    var rtc = {
        PLUGINNAME: 'qtype_recordrtc',
        TEMPLATE: '' +
        '<div class="{{PLUGINNAME}} container-fluid">' +
          '<div class="{{bs_row}} hide">' +
            '<div class="{{bs_col}}12">' +
              '<div id="alert-danger" class="alert {{bs_al_dang}}">' +
                '<strong>{{insecurealert_title}}</strong> {{insecurealert}}' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="{{bs_row}} hide">' +
            '{{#if isAudio}}' +
              '<div class="{{bs_col}}1"></div>' +
              '<div class="{{bs_col}}10">' +
                '<audio id="player"></audio>' +
              '</div>' +
              '<div class="{{bs_col}}1"></div>' +
            '{{else}}' +
              '<div class="{{bs_col}}12">' +
                '<video id="player"></video>' +
              '</div>' +
            '{{/if}}' +
          '</div>' +
          '<div class="{{bs_row}}">' +
            '<div class="{{bs_col}}1"></div>' +
            '<div class="{{bs_col}}10">' +
              '<button id="start-stop" class="{{bs_ss_btn}}">{{startrecording}}</button>' +
            '</div>' +
            '<div class="{{bs_col}}1"></div>' +
          '</div>' +
          '<div class="{{bs_row}} hide">' +
            '<div class="{{bs_col}}3"></div>' +
            '<div class="{{bs_col}}6">' +
              '<button id="upload" class="btn btn-primary btn-block">{{attachrecording}}</button>' +
            '</div>' +
            '<div class="{{bs_col}}3"></div>' +
          '</div>' +
        '</div>',

        init: function (strings) {
            M.util.js_pending('rtc');
            console.log(strings);
            console.log('strings');
            console.log(avrecording);
            rtc.createContent('audio');
            //avrecording.M.atto_recordrtc.audiomodule.init(this);
            console.log($('qtype_recordrtc container-fluid'));
            console.log($('qtype_recordrtc container-fluid'));
            M.util.js_complete('rtc');

        },

    /**
     * Create the HTML to be displayed
     *
     * @method _createContent
     * @param {string} type
     * @returns {Object}
     * @private
     */
    createContent: function(type) {
        console.log(type);
        console.log(avrecording.abstractmodule);
        console.log($('qtype_recordrtc container-fluid'));
        var isAudio = (type === 'audio'),
                bsRow = 'row',
                bsCol = 'col-xs-',
                bsAlDang = 'alert-danger',
                bsSsBtn = 'btn btn-lg btn-outline-danger btn-block';

        var bodyContent = Y.Handlebars.compile(rtc.TEMPLATE)({
                PLUGINNAME: rtc.PLUGINNAME,
                isAudio: isAudio,
                bs_row: bsRow,
                bs_col: bsCol,
                bs_al_dang: bsAlDang,
                bs_ss_btn: bsSsBtn,
                insecurealert_title: M.util.get_string('insecurealert_title', 'qtype_recordrtc'),
                insecurealert: M.util.get_string('insecurealert', 'qtype_recordrtc'),
                startrecording: M.util.get_string('startrecording', 'qtype_recordrtc'),
                attachrecording: M.util.get_string('attachrecording', 'qtype_recordrtc')
            });
        return bodyContent;
    }
};
    /**
     * @alias module:qtype_recordrtc/recordrtc
     */
    return {
        /**
         * Initialise the form JavaScript features.
         */
        init: rtc.init
    };
});
