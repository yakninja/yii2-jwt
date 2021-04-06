<?php

namespace sizeg\jwt\tests;

use Lcobucci\JWT\Signer\Key\InMemory;

class JwtTest extends TestCase
{

    /**
     * Secret key
     */
    const SECRET = 'secret';

    /**
     * Issuer
     */
    const ISSUER = 'http://example.com';

    /**
     * Audience
     */
    const AUDIENCE = 'http://example.org';

    /**
     * Id
     */
    const ID = '4f1g23a12aa';

    /**
     * @var Jwt
     */
    public $jwt;

    /**
     * @ineritdoc
     */
    public function setUp()
    {
        $this->jwt = \Yii::createObject(\sizeg\jwt\Jwt::class, [
            ['key' => self::SECRET]
        ]);
    }

    /**
     * @return Sha256 signer
     */
    public function getSignerSha256()
    {
        return new \Lcobucci\JWT\Signer\Hmac\Sha256();
    }

    /**
     * @return Token created token
     */
    public function createTokenWithSignature()
    {
        $now = new \DateTimeImmutable();
        $expire = new \DateTimeImmutable('now + 3600 seconds');
        return $this->jwt->getBuilder()->setIssuer(self::ISSUER) // Configures the issuer (iss claim)
                ->setAudience(self::AUDIENCE) // Configures the audience (aud claim)
                ->setId(self::ID) // Configures the id (jti claim)
                ->setIssuedAt($now) // Configures the time that the token was issue (iat claim)
                ->setExpiration($expire) // Configures the expiration time of the token (nbf claim)
                ->set('uid', 1) // Configures a new claim, called "uid"
                // creates a signature using "testing" as key
                ->getToken($this->getSignerSha256(), InMemory::plainText($this->jwt->key)); // Retrieves the generated token
    }

    /**
     * @return ValidationData
     */
    public function getValidationData()
    {
        $data = $this->jwt->getValidationData(); // It will use the current time to validate (iat, nbf and exp)
        $data->setIssuer(self::ISSUER);
        $data->setAudience(self::AUDIENCE);
        $data->setId(self::ID);
        return $data;
    }

    /**
     * Validate token with signature
     */
    public function testValidateTokenWithSignature()
    {
        $token = $this->createTokenWithSignature();
        $data = $this->getValidationData();
        $is_verify = $token->verify($this->getSignerSha256(), InMemory::plainText($this->jwt->key));
        $is_valid = $token->validate($data); // true, because validation information is equals to data contained on the token
        $this->assertTrue($is_verify && $is_valid);
    }

    /**
     * Validate token timeout with signature
     */
    public function testValidateTokenTimeoutWithSignature()
    {
        $token = $this->createTokenWithSignature();
        $data = $this->getValidationData();
        $data->setCurrentTime(time() + 4000); // changing the validation time to future
        $is_verify = $token->verify($this->getSignerSha256(), InMemory::plainText($this->jwt->key));
        $is_valid = $token->validate($data); // false, because token is expired since current time is greater than exp
        $this->assertFalse($is_verify && $is_valid);
    }
}
