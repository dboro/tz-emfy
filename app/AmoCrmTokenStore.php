<?php

namespace App;

use Illuminate\Support\Facades\Storage;
use League\OAuth2\Client\Token\AccessToken;

class AmoCrmTokenStore
{
    protected string $tokenFile = 'token.json';

    public function isToken(): bool
    {
        return Storage::exists($this->tokenFile);
    }
    public function getToken(): AccessToken
    {
        $accessToken = json_decode(Storage::get($this->tokenFile), true);

        return new AccessToken([
            'access_token' => $accessToken['accessToken'],
            'refresh_token' => $accessToken['refreshToken'],
            'expires' => $accessToken['expires'],
            'baseDomain' => $accessToken['baseDomain'],
        ]);
    }

    public function saveToken($accessToken)
    {
        if (isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            $data = [
                'accessToken' => $accessToken['accessToken'],
                'expires' => $accessToken['expires'],
                'refreshToken' => $accessToken['refreshToken'],
                'baseDomain' => $accessToken['baseDomain'],
            ];

            Storage::put($this->tokenFile, json_encode($data));
        } else {
            // ToDo Exception
        }
    }
}
