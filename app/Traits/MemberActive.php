<?php

declare(strict_types=1);

namespace App\Traits;

use App\Modules\Member\Models\MemberActivation;
use Illuminate\Support\Str;

trait MemberActive
{
    protected function getToken()
    {
        return hash_hmac('sha256', Str::random(40), config('app.key'));
    }

    public function createActivation($member)
    {
        $activation = $this->getActivation($member);

        if (! $activation) {
            return $this->createToken($member);
        }

        return $this->regenerateToken($member);
    }

    private function regenerateToken($member)
    {
        $token = $this->getToken();
        MemberActivation::where('member_id', $member->id)->update([
            'activation_code' => $token,
        ]);

        return $token;
    }

    private function createToken($member)
    {
        $token = $this->getToken();
        MemberActivation::insert([
            'member_id' => $member->id,
            'activation_code' => $token,
        ]);

        return $token;
    }

    public function getActivation($member)
    {
        return MemberActivation::where('member_id', $member->id)->first();
    }

    public function getActivationByToken($token)
    {
        return MemberActivation::where('activation_code', $token)->first();
    }

    public function deleteActivation($token)
    {
        MemberActivation::where('activation_code', $token)->delete();
    }
}
