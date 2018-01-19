<?php
/**
 * Join the rewards program
 */
namespace Minds\Core\Rewards;

use Minds\Core\Di\Di;

class Join
{

    /** @var TwoFactor $twofactor */
    private $twofactor;

    /** @var SMSServiceInterface $sms */
    private $sms;

    /** @var PhoneNumberUtil $libphonenumber */
    private $libphonenumber;

    /** @var User $user */
    private $user;

    /** @var int $number */
    private $number;

    /** @var int $code */
    private $code;

    /** @var string $secret */
    private $secret;

    public function __construct($twofactor = null, $sms = null, $libphonenumber = null)
    {
        $this->twofactor = $twofactor ?: Di::_()->get('Security\TwoFactor');
        $this->sms = $sms ?: Di::_()->get('SMS\SNS');
        $this->libphonenumber = $libphonenumber ?: \libphonenumber\PhoneNumberUtil::getInstance();
    }

    public function setUser(&$user)
    {
        $this->user = $user;
        return $this;
    }

    public function setNumber($number)
    {
        $proto = $this->libphonenumber->parse("+$number");
        $this->number = $this->libphonenumber->format($proto, \libphonenumber\PhoneNumberFormat::E164);
        return $this;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
    }

    public function verify()
    {
        $secret = $this->twofactor->createSecret();
        $code = $this->twofactor->getCode($secret);

        $this->sms->send($this->number, $code);

        return $secret;
    }

    public function confirm()
    {
        if ($this->twofactor->verifyCode($this->secret, $this->code, 8)) {
            $this->user->setPhoneNumber($this->number);
            $this->user->setPhoneNumberHash(sha1($this->number));
            $this->user->save();
        } else {
            throw new \Exception('The confirmation failed');
        }

        return true;
    }

}