<?php
/*  Copyright © 2009 Transposh Team (website : http://transposh.org)
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*
 * Provide the admin page for configuring the translation options. eg.  what languages ?
 * who is allowed to translate ?
 */

require_once("core/logging.php");
require_once("core/constants.php");

/*
 * Add transposh to the admin menu.
 */
function transposh_admin_menu()
{
	add_options_page('Transposh','Transposh', 6,'Transposh', 'transposh_admin_page');
}

/*
 * Create the admin page.
 */
function transposh_admin_page()
{
	logger('Entry ' . __METHOD__ , 4);

	if($_POST['transposh_admin_posted'])
	{
		update_admin_options();
	}

	echo '<div class="wrap alternate">'.
         '<h2>Transposh</h2>'.
         '<form action="?page=Transposh" method="post">'.
         '<h3>Supported Languages</h3>';

	insert_supported_langs();
	echo '<br/><h3>Who can translate ?</h3>';
	insert_permissions();

	echo '<br/><h3>Rewrite URLs</h3>';
	insert_permalink_rewrite_option();

	echo '<br/><h3>Enable automatic translation</h3>';
	insert_auto_translate_option();

	echo '<input type="hidden" name="transposh_admin_posted" value="1" />'.
		 '<p class="submit"><input type="submit" value="Save Changes" /></p>'.
		 '</form>'.
		 '</div>';
}

/*
 * Insert supported languages section in admin page
 */
function insert_supported_langs()
{
	global $languages, $tr_plugin_url;

	echo '<script type="text/javascript">'.
        'function chbx_change(lang)'.
        '{'.
            'var view = lang + "_view";'.
            'if(document.getElementById(view).checked)'.
            '{'.
               'var edit = lang + "_edit";'.
                'document.getElementById(edit).checked = true;'.
            '}'.
        '}'.
    '</script>';
	echo '<table><tr>';

	$columns = 2;

	for($hdr=0; $hdr < $columns; $hdr++)
	{
		echo '<th>Language</th><th>Viewable</th><th>Translatable</th>'.
             '<th>Default</th><th>Auto?</th><th style="padding-right: 80px"></th>';
	}

	echo '</tr>';

	foreach($languages as $code => $lang)
	{
		list ($language,$flag,$autot) = explode (",",$lang);
		if($i % $columns == 0)
		{
			echo '<tr>';
		}
		echo "\n";

		$i++;

		echo "<td><img src=\"$tr_plugin_url/img/flags/$flag.png\" alt=\"\"/>&nbsp;$language</td>";
		echo '<td align="center">  <input type="checkbox" id="' . $code .'_view" name="' .
		$code . '_view" onchange="chbx_change(\'' . $code . '\')" ' . is_viewable($code) . '/></td>';
		echo "\n";
		echo '<td align="center">  <input type="checkbox" id="' . $code . '_edit" name="' .
		$code . '_edit" ' . is_editable($code). '/></td>';
		echo "\n";
		echo "<td align=\"center\"><input type=\"radio\" name=\"default_lang\" value=\"$code\" " .
		is_default_lang($code). "/></td>";
        // TODO: Add icons?
        if ($autot) {
            echo "<td>Y</td>";
        } else {
            echo "<td>N</td>";
        }

		if($i % $columns == 0)
		{
			echo '</tr>';
		}
		else
		{
			echo "<td style=\"padding-right: 60px\"></td>";
		}
		echo "\n";
	}

	echo '</table>';
}


/*
 * Determine if the given language code is currentlly editable
 * Return 'checked' if true otherwise ""
 */
function is_editable($code)
{
	$langs = get_option(EDITABLE_LANGS);

	if(strpos($langs, $code) !== FALSE)
	{
		return 'checked="checked"';
	}

	return "";
}

/*
 * Determine if the given language code is currentlly viewable
 * Return 'checked' if true otherwise ""
 */
function is_viewable($code)
{
	$langs = get_option(VIEWABLE_LANGS);
	if(strpos($langs, $code) !== FALSE)
	{
		return 'checked="checked"';
	}

	return "";
}

/*
 * Determine if the given language code is currentlly the default language
 * Return 'checked' if true otherwise ""
 */
function is_default_lang($code)
{
	global $languages;

	$default = get_option(DEFAULT_LANG);

	if(!$languages[$default])
	{
		$default = "en";
	}

	if($default ==  $code)
	{
		return 'checked="checked"';
	}

	return "";
}

/*
 * Insert permissiions section in the admin page
 */
function insert_permissions()
{
	global $wp_roles;

	//display known roles and their permission to translate
	foreach($wp_roles->get_names() as $role_name => $something)
	{
		echo '<input type="checkbox" value="1" name="' . $role_name . '" ' . can_translate($role_name) .
             '/>' . $role_name . '&nbsp;&nbsp;&nbsp;';
	}

	//Add our own custom role
	echo '<input type="checkbox" value="1" name="anonymous" '.
	can_translate('anonymous') . '/> Anonymous';
}

/*
 * Insert the option to enable/disable rewrite of perlmalinks.
 * When disabled only parameters will be used to identify the current language.
 *
 */
function insert_permalink_rewrite_option()
{
	$checked = "";
	if(get_option(ENABLE_PERMALINKS_REWRITE))
	{
		$checked = 'checked="checked"';
	}

	echo '<input type="checkbox" value="1" name="enable_permalinks" '. $checked . '/>'.
		 'Rewrite URLs to be search engine friendly, '.
		 'e.g.  (http://wordpress.org/<strong> en</strong>). '.
         'Requires that permalinks will be enabled.';
}

/*
 * Insert the option to enable/disable automatic translation.
 * Enabled by default.
 */
function insert_auto_translate_option()
{
	$checked = "";
	if(get_option(ENABLE_AUTO_TRANSLATE,1))
	{
		$checked = 'checked="checked"';
	}

	echo '<input type="checkbox" value="1" name="enable_autotranslate" '.$checked.'/>'.
	     'Allow automatic translation of pages (currently using Google Translate)';
}

/*
 * Indicates whether the given role can translate.
 * Return either "checked" or ""
 */
function can_translate($role_name)
{
	global $wp_roles;
	if($role_name != 'anonymous')
	{
		$role = $wp_roles->get_role($role_name);
		if(isset($role) && $role->has_cap(TRANSLATOR))
		{
			return 'checked="checked"';
		}
	}
	else
	{
		$allow_anonymous = get_option(ANONYMOUS_TRANSLATION);
		if($allow_anonymous == "1")
		{
			return 'checked="checked"';
		}
	}

	return "";
}

/*
 * Handle newly posted admin options.
 */
function update_admin_options()
{
	logger('Entry ' . __METHOD__, 4);
	global $wp_roles, $languages;
	$viewable_langs = array();
	$editable_langs = array();

	//update roles and capabilities
	foreach($wp_roles->get_names() as $role_name => $something)
	{
		$role = $wp_roles->get_role($role_name);
		if($_POST[$role_name] == "1")
		{
			$role->add_cap(TRANSLATOR);
		}
		else
		{
			$role->remove_cap(TRANSLATOR);
		}
	}

	//Anonymous needs to be handled differently as it does not have a role
	if($_POST['anonymous'] == "1")
	{
		update_option(ANONYMOUS_TRANSLATION, 1);
	}
	else
	{
		update_option(ANONYMOUS_TRANSLATION, 0);
	}


	//Update the list of supported/editable languages
	foreach($languages as $code => $lang)
	{
		if($_POST[$code . '_view'])
		{
			$viewable_langs[] = $code;
		}

		if($_POST[$code . '_edit'])
		{
			$editable_langs[] = $code;
		}
	}

	update_option(VIEWABLE_LANGS, implode(',', $viewable_langs));
	update_option(EDITABLE_LANGS, implode(',', $editable_langs));
	update_option(DEFAULT_LANG,   $_POST['default_lang']);

	if(get_option(ENABLE_PERMALINKS_REWRITE) != $_POST['enable_permalinks'])
	{
		global $wp_rewrite;
		update_option(ENABLE_PERMALINKS_REWRITE, $_POST['enable_permalinks']);

		//rewrite rules
		add_filter('rewrite_rules_array', 'update_rewrite_rules');
		$wp_rewrite->flush_rules();
	}

	if(get_option(ENABLE_AUTO_TRANSLATE,1) != $_POST['enable_autotranslate'])
	{
		update_option(ENABLE_AUTO_TRANSLATE, $_POST['enable_autotranslate']);
	}

	echo '<div id="message" class="updated fade">';
	echo '<p>Changes saved</p>';
	echo '</div>';
}

add_action('admin_menu', 'transposh_admin_menu');
?>