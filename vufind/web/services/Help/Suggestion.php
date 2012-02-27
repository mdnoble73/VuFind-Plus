<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
 

require_once 'Action.php';
require_once('recaptcha/recaptchalib.php');
require_once 'Drivers/marmot_inc/UserSuggestion.php';
require_once 'sys/Mailer.php';

class Suggestion extends Action
{
    function launch()
    {
        global $interface;
        global $configArray;
        
        $suggestion = $_REQUEST['suggestion'];
        $name = $_REQUEST['name'];
        $email = $_REQUEST['email'];
        
        if ($_REQUEST['submit']){
            $isValid = true;
            //Perform validation 
            if (isset($email) && strlen($email) > 0){
                if (!preg_match('/^[A-Z0-9._%-]+@[A-Z0-9.-]+\\.[A-Z]{2,4}$/i', $_REQUEST['email'])){
                    $interface->assign('message', 'The email address provided does not appear to be valid.');
                    $isValid = false;
                }
            }
            if ($isValid && (!isset($suggestion) || strlen($suggestion) == 0)){
                $interface->assign('message', 'You must enter a suggestion.');
                $isValid = false;
            }
            if ($isValid){
                //Process the submission
                $privatekey = "6LeZ8roSAAAAALZTc7Xe-GN5YG1VMeRKAZNscHmb";
                $resp = recaptcha_check_answer ($privatekey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $_POST["recaptcha_challenge_field"],
                                    $_POST["recaptcha_response_field"]);
    
                if (!$resp->is_valid) {
                    $interface->assign('message', 'The CAPTCHA response was incorrect, please try again.');
                    $isValid = false;
                }
            }
            if ($isValid){
                //Save the form.
                $userSuggestion = new UserSuggestion();
                $userSuggestion->name = $name;
                $userSuggestion->email = $email;
                $userSuggestion->suggestion = $suggestion;
                $userSuggestion->hide = 0;
                $userSuggestion->internalNotes = '';
                $userSuggestion->insert();
                
                //After the suggestion has been inserted, e-mail it to the appropriate user. 
                if (isset($configArray['Site']['suggestionEmail']) && strlen($configArray['Site']['suggestionEmail']) > 0){
                    $mail = new VuFindMailer();
                    $to = $configArray['Site']['suggestionEmail'];
                    $from = $configArray['Site']['email'];
                    $subject = "New Suggestion within VuFind";
                    $body = "Suggestion from: $name ($email)\r\n$suggestion";
                    $mail->send($to, $from, $subject, $body);
                }
                
                
                //Redirect to the confirmation page
                header("Location: " . $configArray['Site']['url'] . '/Help/SuggestionConfirm');
                die();
            }
                        
        }
        //Display the form asking for input
        $publickey = "6LeZ8roSAAAAAN0V9pSDie9Z9d-3bhNjsDLBjt1q"; // you got this from the signup page
        
        $captchaCode = recaptcha_get_html($publickey);
        $interface->assign('captcha', $captchaCode);
        $interface->assign('name', $name);
        $interface->assign('email', $email);
        $interface->assign('suggestion', $suggestion);
        
        $interface->setPageTitle('Make a Suggestion');
        $interface->assign('subTemplate', 'suggestion.tpl');
        $interface->setTemplate('view-alt.tpl');
        $interface->display('layout.tpl', 'Suggestion');
        
    }
}