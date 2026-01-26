<?php
/**
 * FOSSBilling-TMCH module
 *
 * Written in 2024–2026 by Taras Kondratyuk (https://namingo.org)
 * Based on example modules and inspired by existing modules of FOSSBilling
 * (https://www.fossbilling.org) and BoxBilling.
 *
 * @license Apache-2.0
 * @see https://www.apache.org/licenses/LICENSE-2.0
 */

namespace Box\Mod\TMCH\Api;

class Admin extends \Api_Abstract
{
    /**
     * Save TMCH credentials.
     *
     * Stores the TMCH username and password for claims notice access.
     *
     * @param array $data
     * @return int Last saved setting ID
     */
    public function settings($data)
    {
        $required = [
            'username' => 'TMCH username is not configured',
            'password' => 'TMCH password is not configured',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $user = trim((string)$data['username']);
        $pass = (string)$data['password'];
        $tmchTest = !empty($data['tmch_test']) ? '1' : '0';

        $this->upsertMeta('username', $user);
        $this->upsertMeta('password', $pass);
        $bean = $this->upsertMeta('tmch_test', $tmchTest);

        $id = $bean->id;

        $this->di['logger']->info('TMCH credentials configured successfully');

        return (int) $id;
    }

    private function upsertMeta(string $key, string $value)
    {
        $sql = "extension = :ext AND meta_key = :key";
        $values = [
            'ext' => 'mod_tmch',
            'key' => $key,
        ];

        $bean = $this->di['db']->findOne('extension_meta', $sql, $values);

        if (!$bean) {
            $bean = $this->di['db']->dispense('extension_meta');
            $bean->extension = 'mod_tmch';
            $bean->meta_key = $key;
            $bean->created_at = date('Y-m-d H:i:s');
        }

        $bean->meta_value = $value;
        $bean->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($bean);

        return $bean;
    }

}