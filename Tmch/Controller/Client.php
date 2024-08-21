<?php

/**
 * FOSSBilling.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license   Apache-2.0
 *
 * Copyright FOSSBilling 2022
 * This software may contain code previously used in the BoxBilling project.
 * Copyright BoxBilling, Inc 2011-2021
 *
 * This source file is subject to the Apache-2.0 License that is bundled
 * with this source code in the file LICENSE
 */

namespace Box\Mod\Tmch\Controller;

class Client implements \FOSSBilling\InjectionAwareInterface
{
    protected $di;

    public function setDi(\Pimple\Container|null $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    /**
     * Methods maps client areas urls to corresponding methods
     * Always use your module prefix to avoid conflicts with other modules
     * in future.
     *
     * @param \Box_App $app - returned by reference
     */
    public function register(\Box_App &$app): void
    {
        $app->get('/tmch', 'get_index', [], static::class);
    }

    public function get_index(\Box_App $app)
    {
        // Access GET parameters and sanitize the lookupKey
        $lookupKey = filter_input(INPUT_GET, 'lookupKey', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $url = "https://test.tmcnis.org/cnis/".$lookupKey.".xml";
        $username = "";
        $password = "@";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $xml = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
        }
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($xml) {
            $xml_object = simplexml_load_string($xml);
            $xml_object->registerXPathNamespace("tmNotice", "urn:ietf:params:xml:ns:tmNotice-1.0");
            $claims = $xml_object->xpath('//tmNotice:claim');

            $note = "This message is a notification that you have applied for a domain name that matches a trademark record submitted to the Trademark Clearinghouse. Your eligibility to register this domain name will depend on your intended use and if it is similar or relates to the trademarks listed below.".PHP_EOL;

            $note .= "Please be aware that your rights to register this domain name may not be protected as a noncommercial use or 'fair use' in accordance with the laws of your country. It is crucial that you read and understand the trademark information provided, including the trademarks, jurisdictions, and goods and services for which the trademarks are registered.".PHP_EOL;

            $note .= "It's also important to note that not all jurisdictions review trademark applications closely, so some of the trademark information may exist in a national or regional registry that does not conduct a thorough review of trademark rights prior to registration. If you have any questions, it's recommended that you consult with a legal expert or attorney on trademarks and intellectual property for guidance.".PHP_EOL;

            $note .= "By continuing with this registration, you're representing that you have received this notice and understand it and, to the best of your knowledge, your registration and use of the requested domain name will not infringe on the trademark rights listed below.".PHP_EOL;

            $note .= "The following ".count($claims)." marks are listed in the Trademark Clearinghouse:".PHP_EOL;

            $markName = $xml_object->xpath('//tmNotice:markName');
            $jurDesc = $xml_object->xpath('//tmNotice:jurDesc');
            $class_desc = $xml_object->xpath('//tmNotice:classDesc');

            $note .= PHP_EOL;

            $claims = $xml_object->xpath('//tmNotice:claim');
            foreach($claims as $claim){
                $elements = $claim->xpath('.//*');
                $first_element_a = true;
                $first_element_b = true;
                foreach ($elements as $element) {
                    $element_name = trim($element->getName());
                    $element_text = trim((string)$element);
                    if (!empty($element_name) && !empty($element_text)) {
                        if ($element->xpath('..')[0]->getName() == "holder" && $first_element_a) {
                            $note .= "Trademark Registrant: ". PHP_EOL;
                            $first_element_a = false;
                        }
                        if ($element->xpath('..')[0]->getName() == "contact" && $first_element_b) {
                            $note .= "Trademark Contact: ". PHP_EOL;
                            $first_element_b = false;
                        }
                        $note .= $element_name . ": " . $element_text . PHP_EOL;
                    }
                }
                $note .= PHP_EOL;
            }
        } else {
            $error = 'No claims notice loaded';
        }

        return $app->render('mod_tmch_index', [
            'note' => $note,
            'error' => $error,
        ]);

    }

}