<?php

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

/*
 * This file handles functions relevant to specific third party plugins
 */

class transposh_mail {

    /** @var transposh_plugin Container class */
    private $transposh;

    /**
     * Construct our class
     * @param transposh_plugin $transposh
     */
    function __construct(&$transposh) {
        $this->transposh = &$transposh;

        add_action('transposh_human_translation', array(&$this, 'transposh_mail_humantranslation'), 10, 4);
    }

    /**
     * Add an item to the activity string upon translation
     * @global object $bp the global buddypress
     * @param string $translation
     * @param string $original
     * @param string $lang
     */
    function transposh_mail_humantranslation($translation, $original, $lang, $translated_by) {

        $to = get_site_option('admin_email');
        $headers = '';
        $subject = __('A new translation was just posted to your site', TRANSPOSH_TEXT_DOMAIN);
        $body = __('The following translation was just added to your site', TRANSPOSH_TEXT_DOMAIN) . "\n\n"
                . __('Original string', TRANSPOSH_TEXT_DOMAIN) . ": $original\n"
                . __('Translation', TRANSPOSH_TEXT_DOMAIN) . ": $translation\n"
                . __('Language', TRANSPOSH_TEXT_DOMAIN) . ": $lang\n"
                . __('Translated by', TRANSPOSH_TEXT_DOMAIN) . ": $translated_by\n\n"
                . __('If you believe that this translation is not good, use the translation editor to modify it', TRANSPOSH_TEXT_DOMAIN) . "\n\n"
                . __('Team Transposh', TRANSPOSH_TEXT_DOMAIN) . "\n\n"
        ;
        wp_mail($to, wp_specialchars_decode($subject), $body, $headers);
    }

}
