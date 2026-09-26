<?php

class DeviceClientFactory
{
    public static function make(array $device): ShellyClient
    {
        return new ShellyClient(
            $device['ip'],
            $device['username'] ?: null,
            $device['password_enc'] ? Crypto::decrypt($device['password_enc']) : null
        );
    }
}
