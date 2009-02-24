<?php

/*
 * Provide the admin page for configuring the translation options. eg.  what languages ?
 * who is allowed to translate ? 
 * 
 */

require_once("logging.php");
require_once("constants.php");


/*
 * Add transposh to the admin menu. 
 *
 */
function transposh_admin_menu()
{
    add_options_page('Transposh','Transposh', 6,'Transposh', 'transposh_admin_page');
}


/*
 * Create the admin page. 
 *
 */
function transposh_admin_page()
{
    logger('Entry ' . __METHOD__ , 4);

    if($_POST['transposh_admin_posted'])
    {
		update_admin_options();
	}

    echo '<div class="wrap alternate">
         <h2>Transposh</h2>
         <form action="?page=Transposh" method="post">
             <h3>Supported Languages</h3>';

    insert_supported_langs();
    echo '<br/> <h3>Who can translate ?</h3>';
    insert_permissions();

    echo '<input type="hidden" name="transposh_admin_posted" value="1" />
          <p class="submit"><input type="submit" value="Save Changes" /></p>
          </form>
          </div>';
}

/*
 * Insert supported languages section in admin page
 *
 */
function insert_supported_langs()
{
    global $languages, $plugin_url;
    
    echo '
    <script type="text/javascript" >
        function chbx_change(lang)
        {
            var view = lang + "_view";
            if(document.getElementById(view).checked)
            {
                var edit = lang + "_edit";
                document.getElementById(edit).checked = true;
            }
            
        }
    </script>
          
    <table>
    <tr>';
    

    $columns = 3;
    
    for($hdr=0; $hdr < $columns; $hdr++)
    {
        echo '<th>Language</th> <th>Viewable</th> <th>Translatable</th> <th></th>';
    }
    
    echo '</tr>';
    
    foreach($languages as $code => $lang)
    {
    	list ($language,$flag) = explode (",",$lang);
        if($i % $columns == 0)
        {
            echo '</tr>';
        }
        echo "\n";
        
        $i++;
        
        echo "<td><img src=\"$plugin_url/flags/$flag.png\"/>&nbsp;$language</td>";
        echo '<td align="center">  <input type="checkbox" id="' . $code .'_view" name="' .
            $code . '_view" onChange="chbx_change(\'' . $code . '\')"' . is_viewable($code) . '/></td>';
        echo "\n";
        
        echo '<td align="center">  <input type="checkbox" id="' . $code . '_edit" name="' .
            $code . '_edit" ' . is_editable($code). '/></td>';
                
        if($i % $columns == 0)
        {
            echo '</tr>';
        }
        else
        {
            echo "<td>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp</td>";
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
    
    if(strstr($langs, $code))
    {
        return "checked";
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
    if(strstr($langs, $code))
    {
        return "checked";
    }
    
    return "";
}

/*
 * Insert permissiions section in the admin page
 *
 */
function insert_permissions()
{
    global $wp_roles;

    //display known roles and their permission to translate
    foreach($wp_roles->get_names() as $role_name => $something)
    {
        echo '<input type="checkbox" value="1" name="' . $role_name . '" ' . can_translate($role_name) .
             '" />' . $role_name . '&nbsp&nbsp&nbsp</input>';
    }

    //Add our own custom role
    echo '<input type="checkbox" value="1" name="anonymous"'     .
            can_translate('anonymous') . '" /> Anonymous</input>';
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
            return 'checked';
        }
    }
    else
    {
        $allow_anonymous = get_option(ANONYMOUS_TRANSLATION);
        if($allow_anonymous == "1")
        {
            return 'checked';
        }
    }
    
    return "";
}

/*
 * Handle newly posted admin options. 
 *
 */ 
function update_admin_options()
{
    logger('Entry ' . __METHOD__, 4);
    global $wp_roles, $languages;

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
    
    echo '<div id="message"class="updated fade">';	
    echo ('<p> Changes saved</p>');			
    echo '</div>';
}

add_action('admin_menu', 'transposh_admin_menu');

?>