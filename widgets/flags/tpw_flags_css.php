<?php

/*
  Plugin Name: Flags (With CSS)
  Plugin URI: http://transposh.org/
  Description: Widget with flags links (Using css sprites)
  Author: Team Transposh
  Version: 1.0
  Author URI: http://transposh.org/
  License: GPL (http://www.gnu.org/licenses/gpl.txt)
 */

/*
 * Transposh v%VERSION%
 * http://transposh.org/
 *
 * Copyright %YEAR%, Team Transposh
 * Licensed under the GPL Version 2 or higher.
 * http://transposh.org/license
 *
 * Date: %DATE%
 */

class tpw_flags_css extends transposh_base_widget {

    /**
     * Creates the list of flags (with css)
     * @param array $args - http://trac.transposh.org/wiki/WidgetWritingGuide#functiontp_widgets_doargs
     */
    static function tp_widget_do($args) {
        echo "<div class=\"" . NO_TRANSLATE_CLASS . " transposh_flags\" >";
        foreach ($args as $langrecord) {
            echo "<a href=\"{$langrecord['url']}\"" . ($langrecord['active'] ? ' class="tr_active"' : '' ) . '>' .
            transposh_utils::display_flag("", $langrecord['flag'], $langrecord['langorig'], true) .
            "</a>";
        }
        echo "</div>";
    }

}

?>
