<?php

/**
 * mcrypt polyfill
 *
 * PHP 7.1 removed the mcrypt extension. This provides a compatibility layer for legacy applications.
 *
 * PHP versions 5 and 7
 *
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
use  SymmetricKey as Base;
abstract class SymmetricKey
{
    /**
     * Encrypt / decrypt using the Counter mode.
     *
     * Set to -1 since that's what Crypt/Random.php uses to index the CTR mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Counter_.28CTR.29
     * @access public
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     */
    const MODE_CTR = -1;
    /**
     * Encrypt / decrypt using the Electronic Code Book mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Electronic_codebook_.28ECB.29
     * @access public
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     */
    const MODE_ECB = 1;
    /**
     * Encrypt / decrypt using the Code Book Chaining mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Cipher-block_chaining_.28CBC.29
     * @access public
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     */
    const MODE_CBC = 2;
    /**
     * Encrypt / decrypt using the Cipher Feedback mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Cipher_feedback_.28CFB.29
     * @access public
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     */
    const MODE_CFB = 3;
    /**
     * Encrypt / decrypt using the Cipher Feedback mode (8bit)
     *
     * @access public
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     */
    const MODE_CFB8 = 38;
    /**
     * Encrypt / decrypt using the Output Feedback mode.
     *
     * @link http://en.wikipedia.org/wiki/Block_cipher_modes_of_operation#Output_feedback_.28OFB.29
     * @access public
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     */
    const MODE_OFB = 4;
    /**
     * Encrypt / decrypt using Galois/Counter mode.
     *
     * @link https://en.wikipedia.org/wiki/Galois/Counter_Mode
     * @access public
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     */
    const MODE_GCM = 5;
    /**
     * Encrypt / decrypt using streaming mode.
     *
     * @access public
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     */
    const MODE_STREAM = 6;

    /**
     * Mode Map
     *
     * @access private
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     */
    const MODE_MAP = [
        'ctr'    => self::MODE_CTR,
        'ecb'    => self::MODE_ECB,
        'cbc'    => self::MODE_CBC,
        'cfb'    => self::MODE_CFB,
        'cfb8'   => self::MODE_CFB8,
        'ofb'    => self::MODE_OFB,
        'gcm'    => self::MODE_GCM,
        'stream' => self::MODE_STREAM
    ];

    /**
     * Base value for the internal implementation $engine switch
     *
     * @access private
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     */
    const ENGINE_INTERNAL = 1;
    /**
     * Base value for the eval() implementation $engine switch
     *
     * @access private
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     */
    const ENGINE_EVAL = 2;
    /**
     * Base value for the mcrypt implementation $engine switch
     *
     * @access private
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     */
    const ENGINE_MCRYPT = 3;
    /**
     * Base value for the openssl implementation $engine switch
     *
     * @access private
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     */
    const ENGINE_OPENSSL = 4;
    /**
     * Base value for the libsodium implementation $engine switch
     *
     * @access private
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     */
    const ENGINE_LIBSODIUM = 5;
    /**
     * Base value for the openssl / gcm implementation $engine switch
     *
     * @access private
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     */
    const ENGINE_OPENSSL_GCM = 6;

    /**
     * Engine Reverse Map
     *
     * @access private
     * @see \phpseclib3\Crypt\Common\SymmetricKey::getEngine()
     */
    const ENGINE_MAP = [
        self::ENGINE_INTERNAL    => 'PHP',
        self::ENGINE_EVAL        => 'Eval',
        self::ENGINE_MCRYPT      => 'mcrypt',
        self::ENGINE_OPENSSL     => 'OpenSSL',
        self::ENGINE_LIBSODIUM   => 'libsodium',
        self::ENGINE_OPENSSL_GCM => 'OpenSSL (GCM)'
    ];

    /**
     * The Encryption Mode
     *
     * @see self::__construct()
     * @var int
     * @access private
     */
    protected $mode;

    /**
     * The Block Length of the block cipher
     *
     * @var int
     * @access private
     */
    protected $block_size = 16;

    /**
     * The Key
     *
     * @see self::setKey()
     * @var string
     * @access private
     */
    protected $key = false;

    /**
     * The Initialization Vector
     *
     * @see self::setIV()
     * @var string
     * @access private
     */
    private $iv = false;

    /**
     * A "sliding" Initialization Vector
     *
     * @see self::enableContinuousBuffer()
     * @see self::clearBuffers()
     * @var string
     * @access private
     */
    protected $encryptIV;

    /**
     * A "sliding" Initialization Vector
     *
     * @see self::enableContinuousBuffer()
     * @see self::clearBuffers()
     * @var string
     * @access private
     */
    protected $decryptIV;

    /**
     * Continuous Buffer status
     *
     * @see self::enableContinuousBuffer()
     * @var bool
     * @access private
     */
    protected $continuousBuffer = false;

    /**
     * Encryption buffer for CTR, OFB and CFB modes
     *
     * @see self::encrypt()
     * @see self::clearBuffers()
     * @var array
     * @access private
     */
    protected $enbuffer;

    /**
     * Decryption buffer for CTR, OFB and CFB modes
     *
     * @see self::decrypt()
     * @see self::clearBuffers()
     * @var array
     * @access private
     */
    protected $debuffer;

    /**
     * mcrypt resource for encryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @see self::encrypt()
     * @var resource
     * @access private
     */
    private $enmcrypt;

    /**
     * mcrypt resource for decryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @see self::decrypt()
     * @var resource
     * @access private
     */
    private $demcrypt;

    /**
     * Does the enmcrypt resource need to be (re)initialized?
     *
     * @see \phpseclib3\Crypt\Twofish::setKey()
     * @see \phpseclib3\Crypt\Twofish::setIV()
     * @var bool
     * @access private
     */
    private $enchanged = true;

    /**
     * Does the demcrypt resource need to be (re)initialized?
     *
     * @see \phpseclib3\Crypt\Twofish::setKey()
     * @see \phpseclib3\Crypt\Twofish::setIV()
     * @var bool
     * @access private
     */
    private $dechanged = true;

    /**
     * mcrypt resource for CFB mode
     *
     * mcrypt's CFB mode, in (and only in) buffered context,
     * is broken, so phpseclib implements the CFB mode by it self,
     * even when the mcrypt php extension is available.
     *
     * In order to do the CFB-mode work (fast) phpseclib
     * use a separate ECB-mode mcrypt resource.
     *
     * @link http://phpseclib.sourceforge.net/cfb-demo.phps
     * @see self::encrypt()
     * @see self::decrypt()
     * @see self::setupMcrypt()
     * @var resource
     * @access private
     */
    private $ecb;

    /**
     * Optimizing value while CFB-encrypting
     *
     * Only relevant if $continuousBuffer enabled
     * and $engine == self::ENGINE_MCRYPT
     *
     * It's faster to re-init $enmcrypt if
     * $buffer bytes > $cfb_init_len than
     * using the $ecb resource furthermore.
     *
     * This value depends of the chosen cipher
     * and the time it would be needed for it's
     * initialization [by mcrypt_generic_init()]
     * which, typically, depends on the complexity
     * on its internaly Key-expanding algorithm.
     *
     * @see self::encrypt()
     * @var int
     * @access private
     */
    protected $cfb_init_len = 600;

    /**
     * Does internal cipher state need to be (re)initialized?
     *
     * @see self::setKey()
     * @see self::setIV()
     * @see self::disableContinuousBuffer()
     * @var bool
     * @access private
     */
    protected $changed = true;

    /**
     * Does Eval engie need to be (re)initialized?
     *
     * @see self::setup()
     * @var bool
     * @access private
     */
    protected $nonIVChanged = true;

    /**
     * Padding status
     *
     * @see self::enablePadding()
     * @var bool
     * @access private
     */
    private $padding = true;

    /**
     * Is the mode one that is paddable?
     *
     * @see self::__construct()
     * @var bool
     * @access private
     */
    private $paddable = false;

    /**
     * Holds which crypt engine internaly should be use,
     * which will be determined automatically on __construct()
     *
     * Currently available $engines are:
     * - self::ENGINE_LIBSODIUM   (very fast, php-extension: libsodium, extension_loaded('libsodium') required)
     * - self::ENGINE_OPENSSL_GCM (very fast, php-extension: openssl, extension_loaded('openssl') required)
     * - self::ENGINE_OPENSSL     (very fast, php-extension: openssl, extension_loaded('openssl') required)
     * - self::ENGINE_MCRYPT      (fast, php-extension: mcrypt, extension_loaded('mcrypt') required)
     * - self::ENGINE_EVAL        (medium, pure php-engine, no php-extension required)
     * - self::ENGINE_INTERNAL    (slower, pure php-engine, no php-extension required)
     *
     * @see self::setEngine()
     * @see self::encrypt()
     * @see self::decrypt()
     * @var int
     * @access private
     */
    protected $engine;

    /**
     * Holds the preferred crypt engine
     *
     * @see self::setEngine()
     * @see self::setPreferredEngine()
     * @var int
     * @access private
     */
    private $preferredEngine;

    /**
     * The mcrypt specific name of the cipher
     *
     * Only used if $engine == self::ENGINE_MCRYPT
     *
     * @link http://www.php.net/mcrypt_module_open
     * @link http://www.php.net/mcrypt_list_algorithms
     * @see self::setupMcrypt()
     * @var string
     * @access private
     */
    protected $cipher_name_mcrypt;

    /**
     * The openssl specific name of the cipher
     *
     * Only used if $engine == self::ENGINE_OPENSSL
     *
     * @link http://www.php.net/openssl-get-cipher-methods
     * @var string
     * @access private
     */
    protected $cipher_name_openssl;

    /**
     * The openssl specific name of the cipher in ECB mode
     *
     * If OpenSSL does not support the mode we're trying to use (CTR)
     * it can still be emulated with ECB mode.
     *
     * @link http://www.php.net/openssl-get-cipher-methods
     * @var string
     * @access private
     */
    protected static $cipher_name_openssl_ecb;

    /**
     * The default salt used by setPassword()
     *
     * @see self::setPassword()
     * @var string
     * @access private
     */
    private $password_default_salt = 'phpseclib/salt';

    /**
     * The name of the performance-optimized callback function
     *
     * Used by encrypt() / decrypt()
     * only if $engine == self::ENGINE_INTERNAL
     *
     * @see self::encrypt()
     * @see self::decrypt()
     * @see self::setupInlineCrypt()
     * @var Callback
     * @access private
     */
    protected $inline_crypt;

    /**
     * If OpenSSL can be used in ECB but not in CTR we can emulate CTR
     *
     * @see self::openssl_ctr_process()
     * @var bool
     * @access private
     */
    private $openssl_emulate_ctr = false;

    /**
     * Don't truncate / null pad key
     *
     * @see self::clearBuffers()
     * @var bool
     * @access private
     */
    private $skip_key_adjustment = false;

    /**
     * Has the key length explicitly been set or should it be derived from the key, itself?
     *
     * @see self::setKeyLength()
     * @var bool
     * @access private
     */
    protected $explicit_key_length = false;

    /**
     * Hash subkey for GHASH
     *
     * @see self::setupGCM()
     * @see self::ghash()
     * @var BinaryField\Integer
     * @access private
     */
    private $h;

    /**
     * Additional authenticated data
     *
     * @var string
     * @access private
     */
    protected $aad = '';

    /**
     * Authentication Tag produced after a round of encryption
     *
     * @var string
     * @access private
     */
    protected $newtag = false;

    /**
     * Authentication Tag to be verified during decryption
     *
     * @var string
     * @access private
     */
    protected $oldtag = false;

    /**
     * GCM Binary Field
     *
     * @see self::__construct()
     * @see self::ghash()
     * @var BinaryField
     * @access private
     */
    private static $gcmField;

    /**
     * Poly1305 Prime Field
     *
     * @see self::enablePoly1305()
     * @see self::poly1305()
     * @var PrimeField
     * @access private
     */
    private static $poly1305Field;

    /**
     * Poly1305 Key
     *
     * @see self::setPoly1305Key()
     * @see self::poly1305()
     * @var string
     * @access private
     */
    protected $poly1305Key;

    /**
     * Poly1305 Flag
     *
     * @see self::setPoly1305Key()
     * @see self::enablePoly1305()
     * @var boolean
     * @access private
     */
    protected $usePoly1305 = false;

    /**
     * The Original Initialization Vector
     *
     * GCM uses the nonce to build the IV but we want to be able to distinguish between nonce-derived
     * IV's and user-set IV's
     *
     * @see self::setIV()
     * @var string
     * @access private
     */
    private $origIV = false;

    /**
     * Nonce
     *
     * Only used with GCM. We could re-use setIV() but nonce's can be of a different length and
     * toggling between GCM and other modes could be more complicated if we re-used setIV()
     *
     * @see self::setNonce()
     * @var string
     * @access private
     */
    protected $nonce = false;

    /**
     * Default Constructor.
     *
     * $mode could be:
     *
     * - ecb
     *
     * - cbc
     *
     * - ctr
     *
     * - cfb
     *
     * - cfb8
     *
     * - ofb
     *
     * - gcm
     *
     * @param string $mode
     * @access public
     * @throws BadModeException if an invalid / unsupported mode is provided
     */
    public function __construct($mode)
    {
        $mode = strtolower($mode);
        // necessary because of 5.6 compatibility; we can't do isset(self::MODE_MAP[$mode]) in 5.6
        $map = self::MODE_MAP;
        if (!isset($map[$mode])) {
            throw new BadModeException('No valid mode has been specified');
        }

        $mode = self::MODE_MAP[$mode];

        // $mode dependent settings
        switch ($mode) {
            case self::MODE_ECB:
            case self::MODE_CBC:
                $this->paddable = true;
                break;
            case self::MODE_CTR:
            case self::MODE_CFB:
            case self::MODE_CFB8:
            case self::MODE_OFB:
            case self::MODE_STREAM:
                $this->paddable = false;
                break;
            case self::MODE_GCM:
                if ($this->block_size != 16) {
                    throw new BadModeException('GCM is only valid for block ciphers with a block size of 128 bits');
                }
                if (!isset(self::$gcmField)) {
                    self::$gcmField = new BinaryField(128, 7, 2, 1, 0);
                }
                $this->paddable = false;
                break;
            default:
                throw new BadModeException('No valid mode has been specified');
        }

        $this->mode = $mode;
    }

    /**
     * Sets the initialization vector.
     *
     * setIV() is not required when ecb or gcm modes are being used.
     *
     * {@internal Can be overwritten by a sub class, but does not have to be}
     *
     * @access public
     * @param string $iv
     * @throws \LengthException if the IV length isn't equal to the block size
     * @throws \BadMethodCallException if an IV is provided when one shouldn't be
     */
    public function setIV($iv)
    {
        if ($this->mode == self::MODE_ECB) {
            throw new \BadMethodCallException('This mode does not require an IV.');
        }

        if ($this->mode == self::MODE_GCM) {
            throw new \BadMethodCallException('Use setNonce instead');
        }

        if (!$this->usesIV()) {
            throw new \BadMethodCallException('This algorithm does not use an IV.');
        }

        if (strlen($iv) != $this->block_size) {
            throw new \LengthException('Received initialization vector of size ' . strlen($iv) . ', but size ' . $this->block_size . ' is required');
        }

        $this->iv = $this->origIV = $iv;
        $this->changed = true;
    }

    /**
     * Enables Poly1305 mode.
     *
     * Once enabled Poly1305 cannot be disabled.
     *
     * @access public
     * @throws \BadMethodCallException if Poly1305 is enabled whilst in GCM mode
     */
    public function enablePoly1305()
    {
        if ($this->mode == self::MODE_GCM) {
            throw new \BadMethodCallException('Poly1305 cannot be used in GCM mode');
        }

        $this->usePoly1305 = true;
    }

    /**
     * Enables Poly1305 mode.
     *
     * Once enabled Poly1305 cannot be disabled. If $key is not passed then an attempt to call createPoly1305Key
     * will be made.
     *
     * @access public
     * @param string $key optional
     * @throws \LengthException if the key isn't long enough
     * @throws \BadMethodCallException if Poly1305 is enabled whilst in GCM mode
     */
    public function setPoly1305Key($key = null)
    {
        if ($this->mode == self::MODE_GCM) {
            throw new \BadMethodCallException('Poly1305 cannot be used in GCM mode');
        }

        if (!is_string($key) || strlen($key) != 32) {
            throw new \LengthException('The Poly1305 key must be 32 bytes long (256 bits)');
        }

        if (!isset(self::$poly1305Field)) {
            // 2^130-5
            self::$poly1305Field = new PrimeField(new BigInteger('3fffffffffffffffffffffffffffffffb', 16));
        }

        $this->poly1305Key = $key;
        $this->usePoly1305 = true;
    }

    /**
     * Sets the nonce.
     *
     * setNonce() is only required when gcm is used
     *
     * @access public
     * @param string $nonce
     * @throws \BadMethodCallException if an nonce is provided when one shouldn't be
     */
    public function setNonce($nonce)
    {
        if ($this->mode != self::MODE_GCM) {
            throw new \BadMethodCallException('Nonces are only used in GCM mode.');
        }

        $this->nonce = $nonce;
        $this->setEngine();
    }

    /**
     * Sets additional authenticated data
     *
     * setAAD() is only used by gcm or in poly1305 mode
     *
     * @access public
     * @param string $aad
     * @throws \BadMethodCallException if mode isn't GCM or if poly1305 isn't being utilized
     */
    public function setAAD($aad)
    {
        if ($this->mode != self::MODE_GCM && !$this->usePoly1305) {
            throw new \BadMethodCallException('Additional authenticated data is only utilized in GCM mode or with Poly1305');
        }

        $this->aad = $aad;
    }

    /**
     * Returns whether or not the algorithm uses an IV
     *
     * @access public
     * @return bool
     */
    public function usesIV()
    {
        return $this->mode != self::MODE_GCM && $this->mode != self::MODE_ECB;
    }

    /**
     * Returns whether or not the algorithm uses a nonce
     *
     * @access public
     * @return bool
     */
    public function usesNonce()
    {
        return $this->mode == self::MODE_GCM;
    }

    /**
     * Returns the current key length in bits
     *
     * @access public
     * @return int
     */
    public function getKeyLength()
    {
        return $this->key_length << 3;
    }

    /**
     * Returns the current block length in bits
     *
     * @access public
     * @return int
     */
    public function getBlockLength()
    {
        return $this->block_size << 3;
    }

    /**
     * Returns the current block length in bytes
     *
     * @access public
     * @return int
     */
    public function getBlockLengthInBytes()
    {
        return $this->block_size;
    }

    /**
     * Sets the key length.
     *
     * Keys with explicitly set lengths need to be treated accordingly
     *
     * @access public
     * @param int $length
     */
    public function setKeyLength($length)
    {
        $this->explicit_key_length = $length >> 3;

        if (is_string($this->key) && strlen($this->key) != $this->explicit_key_length) {
            $this->key = false;
            throw new InconsistentSetupException('Key has already been set and is not ' .$this->explicit_key_length . ' bytes long');
        }
    }

    /**
     * Sets the key.
     *
     * The min/max length(s) of the key depends on the cipher which is used.
     * If the key not fits the length(s) of the cipher it will paded with null bytes
     * up to the closest valid key length.  If the key is more than max length,
     * we trim the excess bits.
     *
     * If the key is not explicitly set, it'll be assumed to be all null bytes.
     *
     * {@internal Could, but not must, extend by the child Crypt_* class}
     *
     * @access public
     * @param string $key
     */
    public function setKey($key)
    {
        if ($this->explicit_key_length !== false && strlen($key) != $this->explicit_key_length) {
            throw new InconsistentSetupException('Key length has already been set to ' . $this->explicit_key_length . ' bytes and this key is ' . strlen($key) . ' bytes');
        }

        $this->key = $key;
        $this->key_length = strlen($key);
        $this->setEngine();
    }

    /**
     * Sets the password.
     *
     * Depending on what $method is set to, setPassword()'s (optional) parameters are as follows:
     *     {@link http://en.wikipedia.org/wiki/PBKDF2 pbkdf2} or pbkdf1:
     *         $hash, $salt, $count, $dkLen
     *
     *         Where $hash (default = sha1) currently supports the following hashes: see: Crypt/Hash.php
     *
     * {@internal Could, but not must, extend by the child Crypt_* class}
     *
     * @see Crypt/Hash.php
     * @param string $password
     * @param string $method
     * @param string[] ...$func_args
     * @throws \LengthException if pbkdf1 is being used and the derived key length exceeds the hash length
     * @return bool
     * @access public
     */
    public function setPassword($password, $method = 'pbkdf2', ...$func_args)
    {
        $key = '';

        $method = strtolower($method);
        switch ($method) {
            case 'pkcs12': // from https://tools.ietf.org/html/rfc7292#appendix-B.2
            case 'pbkdf1':
            case 'pbkdf2':
                // Hash function
                $hash = isset($func_args[0]) ? strtolower($func_args[0]) : 'sha1';
                $hashObj = new Hash();
                $hashObj->setHash($hash);

                // WPA and WPA2 use the SSID as the salt
                $salt = isset($func_args[1]) ? $func_args[1] : $this->password_default_salt;

                // RFC2898#section-4.2 uses 1,000 iterations by default
                // WPA and WPA2 use 4,096.
                $count = isset($func_args[2]) ? $func_args[2] : 1000;

                // Keylength
                if (isset($func_args[3])) {
                    if ($func_args[3] <= 0) {
                        throw new \LengthException('Derived key length cannot be longer 0 or less');
                    }
                    $dkLen = $func_args[3];
                } else {
                    $key_length = $this->explicit_key_length !== false ? $this->explicit_key_length : $this->key_length;
                    $dkLen = $method == 'pbkdf1' ? 2 * $key_length : $key_length;
                }

                switch (true) {
                    case $method == 'pkcs12':
                        /*
                         In this specification, however, all passwords are created from
                         BMPStrings with a NULL terminator.  This means that each character in
                         the original BMPString is encoded in 2 bytes in big-endian format
                         (most-significant byte first).  There are no Unicode byte order
                         marks.  The 2 bytes produced from the last character in the BMPString
                         are followed by 2 additional bytes with the value 0x00.

                         -- https://tools.ietf.org/html/rfc7292#appendix-B.1
                         */
                        $password = "\0". chunk_split($password, 1, "\0") . "\0";

                        /*
                         This standard specifies 3 different values for the ID byte mentioned
                         above:

                         1.  If ID=1, then the pseudorandom bits being produced are to be used
                             as key material for performing encryption or decryption.

                         2.  If ID=2, then the pseudorandom bits being produced are to be used
                             as an IV (Initial Value) for encryption or decryption.

                         3.  If ID=3, then the pseudorandom bits being produced are to be used
                             as an integrity key for MACing.
                         */
                        // Construct a string, D (the "diversifier"), by concatenating v/8
                        // copies of ID.
                        $blockLength = $hashObj->getBlockLengthInBytes();
                        $d1 = str_repeat(chr(1), $blockLength);
                        $d2 = str_repeat(chr(2), $blockLength);
                        $s = '';
                        if (strlen($salt)) {
                            while (strlen($s) < $blockLength) {
                                $s.= $salt;
                            }
                        }
                        $s = substr($s, 0, $blockLength);

                        $p = '';
                        if (strlen($password)) {
                            while (strlen($p) < $blockLength) {
                                $p.= $password;
                            }
                        }
                        $p = substr($p, 0, $blockLength);

                        $i = $s . $p;

                        $this->setKey(self::pkcs12helper($dkLen, $hashObj, $i, $d1, $count));
                        if ($this->usesIV()) {
                            $this->setIV(self::pkcs12helper($this->block_size, $hashObj, $i, $d2, $count));
                        }

                        return true;
                    case $method == 'pbkdf1':
                        if ($dkLen > $hashObj->getLengthInBytes()) {
                            throw new \LengthException('Derived key length cannot be longer than the hash length');
                        }
                        $t = $password . $salt;
                        for ($i = 0; $i < $count; ++$i) {
                            $t = $hashObj->hash($t);
                        }
                        $key = substr($t, 0, $dkLen);

                        $this->setKey(substr($key, 0, $dkLen >> 1));
                        if ($this->usesIV()) {
                            $this->setIV(substr($key, $dkLen >> 1));
                        }

                        return true;
                    case !in_array($hash, hash_algos()):
                        $i = 1;
                        $hashObj->setKey($password);
                        while (strlen($key) < $dkLen) {
                            $f = $u = $hashObj->hash($salt . pack('N', $i++));
                            for ($j = 2; $j <= $count; ++$j) {
                                $u = $hashObj->hash($u);
                                $f^= $u;
                            }
                            $key.= $f;
                        }
                        $key = substr($key, 0, $dkLen);
                        break;
                    default:
                        $key = hash_pbkdf2($hash, $password, $salt, $count, $dkLen, true);
                }
                break;
            default:
                throw new UnsupportedAlgorithmException($method . ' is not a supported password hashing method');
        }

        $this->setKey($key);

        return true;
    }

    /**
     * PKCS#12 KDF Helper Function
     *
     * As discussed here:
     *
     * {@link https://tools.ietf.org/html/rfc7292#appendix-B}
     *
     * @see self::setPassword()
     * @access private
     * @param int $n
     * @param \phpseclib3\Crypt\Hash $hashObj
     * @param string $i
     * @param string $d
     * @param int $count
     * @return string $a
     */
    private static function pkcs12helper($n, $hashObj, $i, $d, $count)
    {
        static $one;
        if (!isset($one)) {
            $one = new BigInteger(1);
        }

        $blockLength = $hashObj->getBlockLength() >> 3;

        $c = ceil($n / $hashObj->getLengthInBytes());
        $a = '';
        for ($j = 1; $j <= $c; $j++) {
            $ai = $d . $i;
            for ($k = 0; $k < $count; $k++) {
                $ai = $hashObj->hash($ai);
            }
            $b = '';
            while (strlen($b) < $blockLength) {
                $b.= $ai;
            }
            $b = substr($b, 0, $blockLength);
            $b = new BigInteger($b, 256);
            $newi = '';
            for ($k = 0; $k < strlen($i); $k+= $blockLength) {
                $temp = substr($i, $k, $blockLength);
                $temp = new BigInteger($temp, 256);
                $temp->setPrecision($blockLength << 3);
                $temp = $temp->add($b);
                $temp = $temp->add($one);
                $newi.= $temp->toBytes(false);
            }
            $i = $newi;
            $a.= $ai;
        }

        return substr($a, 0, $n);
    }

    /**
     * Encrypts a message.
     *
     * $plaintext will be padded with additional bytes such that it's length is a multiple of the block size. Other cipher
     * implementations may or may not pad in the same manner.  Other common approaches to padding and the reasons why it's
     * necessary are discussed in the following
     * URL:
     *
     * {@link http://www.di-mgt.com.au/cryptopad.html http://www.di-mgt.com.au/cryptopad.html}
     *
     * An alternative to padding is to, separately, send the length of the file.  This is what SSH, in fact, does.
     * strlen($plaintext) will still need to be a multiple of the block size, however, arbitrary values can be added to make it that
     * length.
     *
     * {@internal Could, but not must, extend by the child Crypt_* class}
     *
     * @see self::decrypt()
     * @access public
     * @param string $plaintext
     * @return string $ciphertext
     */
    public function encrypt($plaintext)
    {
        if ($this->paddable) {
            $plaintext = $this->pad($plaintext);
        }

        $this->setup();

        if ($this->mode == self::MODE_GCM) {
            $oldIV = $this->iv;
            Strings::increment_str($this->iv);
            $cipher = new static('ctr');
            $cipher->setKey($this->key);
            $cipher->setIV($this->iv);
            $ciphertext = $cipher->encrypt($plaintext);

            $s = $this->ghash(
                self::nullPad128($this->aad) .
                self::nullPad128($ciphertext) .
                self::len64($this->aad) .
                self::len64($ciphertext)
            );
            $cipher->encryptIV = $this->iv = $this->encryptIV = $this->decryptIV = $oldIV;
            $this->newtag = $cipher->encrypt($s);
            return $ciphertext;
        }

        if (isset($this->poly1305Key)) {
            $cipher = clone $this;
            unset($cipher->poly1305Key);
            $this->usePoly1305 = false;
            $ciphertext = $cipher->encrypt($plaintext);
            $this->newtag = $this->poly1305($ciphertext);
            return $ciphertext;
        }

        if ($this->engine === self::ENGINE_OPENSSL) {
            switch ($this->mode) {
                case self::MODE_STREAM:
                    return openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
                case self::MODE_ECB:
                    return openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
                case self::MODE_CBC:
                    $result = openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $this->encryptIV);
                    if ($this->continuousBuffer) {
                        $this->encryptIV = substr($result, -$this->block_size);
                    }
                    return $result;
                case self::MODE_CTR:
                    return $this->openssl_ctr_process($plaintext, $this->encryptIV, $this->enbuffer);
                case self::MODE_CFB:
                    // cfb loosely routines inspired by openssl's:
                    // {@link http://cvs.openssl.org/fileview?f=openssl/crypto/modes/cfb128.c&v=1.3.2.2.2.1}
                    $ciphertext = '';
                    if ($this->continuousBuffer) {
                        $iv = &$this->encryptIV;
                        $pos = &$this->enbuffer['pos'];
                    } else {
                        $iv = $this->encryptIV;
                        $pos = 0;
                    }
                    $len = strlen($plaintext);
                    $i = 0;
                    if ($pos) {
                        $orig_pos = $pos;
                        $max = $this->block_size - $pos;
                        if ($len >= $max) {
                            $i = $max;
                            $len-= $max;
                            $pos = 0;
                        } else {
                            $i = $len;
                            $pos+= $len;
                            $len = 0;
                        }
                        // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $blocksize
                        $ciphertext = substr($iv, $orig_pos) ^ $plaintext;
                        $iv = substr_replace($iv, $ciphertext, $orig_pos, $i);
                        $plaintext = substr($plaintext, $i);
                    }

                    $overflow = $len % $this->block_size;

                    if ($overflow) {
                        $ciphertext.= openssl_encrypt(substr($plaintext, 0, -$overflow) . str_repeat("\0", $this->block_size), $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
                        $iv = Strings::pop($ciphertext, $this->block_size);

                        $size = $len - $overflow;
                        $block = $iv ^ substr($plaintext, -$overflow);
                        $iv = substr_replace($iv, $block, 0, $overflow);
                        $ciphertext.= $block;
                        $pos = $overflow;
                    } elseif ($len) {
                        $ciphertext = openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
                        $iv = substr($ciphertext, -$this->block_size);
                    }

                    return $ciphertext;
                case self::MODE_CFB8:
                    $ciphertext = openssl_encrypt($plaintext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $this->encryptIV);
                    if ($this->continuousBuffer) {
                        if (($len = strlen($ciphertext)) >= $this->block_size) {
                            $this->encryptIV = substr($ciphertext, -$this->block_size);
                        } else {
                            $this->encryptIV = substr($this->encryptIV, $len - $this->block_size) . substr($ciphertext, -$len);
                        }
                    }
                    return $ciphertext;
                case self::MODE_OFB:
                    return $this->openssl_ofb_process($plaintext, $this->encryptIV, $this->enbuffer);
            }
        }

        if ($this->engine === self::ENGINE_MCRYPT) {
            if ($this->enchanged) {
                @mcrypt_generic_init($this->enmcrypt, $this->key, $this->getIV($this->encryptIV));
                $this->enchanged = false;
            }

            // re: {@link http://phpseclib.sourceforge.net/cfb-demo.phps}
            // using mcrypt's default handing of CFB the above would output two different things.  using phpseclib's
            // rewritten CFB implementation the above outputs the same thing twice.
            if ($this->mode == self::MODE_CFB && $this->continuousBuffer) {
                $block_size = $this->block_size;
                $iv = &$this->encryptIV;
                $pos = &$this->enbuffer['pos'];
                $len = strlen($plaintext);
                $ciphertext = '';
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = $block_size - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    $ciphertext = substr($iv, $orig_pos) ^ $plaintext;
                    $iv = substr_replace($iv, $ciphertext, $orig_pos, $i);
                    $this->enbuffer['enmcrypt_init'] = true;
                }
                if ($len >= $block_size) {
                    if ($this->enbuffer['enmcrypt_init'] === false || $len > $this->cfb_init_len) {
                        if ($this->enbuffer['enmcrypt_init'] === true) {
                            @mcrypt_generic_init($this->enmcrypt, $this->key, $iv);
                            $this->enbuffer['enmcrypt_init'] = false;
                        }
                        $ciphertext.= @mcrypt_generic($this->enmcrypt, substr($plaintext, $i, $len - $len % $block_size));
                        $iv = substr($ciphertext, -$block_size);
                        $len%= $block_size;
                    } else {
                        while ($len >= $block_size) {
                            $iv = @mcrypt_generic($this->ecb, $iv) ^ substr($plaintext, $i, $block_size);
                            $ciphertext.= $iv;
                            $len-= $block_size;
                            $i+= $block_size;
                        }
                    }
                }

                if ($len) {
                    $iv = @mcrypt_generic($this->ecb, $iv);
                    $block = $iv ^ substr($plaintext, -$len);
                    $iv = substr_replace($iv, $block, 0, $len);
                    $ciphertext.= $block;
                    $pos = $len;
                }

                return $ciphertext;
            }

            $ciphertext = @mcrypt_generic($this->enmcrypt, $plaintext);

            if (!$this->continuousBuffer) {
                @mcrypt_generic_init($this->enmcrypt, $this->key, $this->getIV($this->encryptIV));
            }

            return $ciphertext;
        }

        if ($this->engine === self::ENGINE_EVAL) {
            $inline = $this->inline_crypt;
            return $inline('encrypt', $plaintext);
        }

        $buffer = &$this->enbuffer;
        $block_size = $this->block_size;
        $ciphertext = '';
        switch ($this->mode) {
            case self::MODE_ECB:
                for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                    $ciphertext.= $this->encryptBlock(substr($plaintext, $i, $block_size));
                }
                break;
            case self::MODE_CBC:
                $xor = $this->encryptIV;
                for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                    $block = substr($plaintext, $i, $block_size);
                    $block = $this->encryptBlock($block ^ $xor);
                    $xor = $block;
                    $ciphertext.= $block;
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                }
                break;
            case self::MODE_CTR:
                $xor = $this->encryptIV;
                if (strlen($buffer['ciphertext'])) {
                    for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                        $block = substr($plaintext, $i, $block_size);
                        if (strlen($block) > strlen($buffer['ciphertext'])) {
                            $buffer['ciphertext'].= $this->encryptBlock($xor);
                        }
                        Strings::increment_str($xor);
                        $key = Strings::shift($buffer['ciphertext'], $block_size);
                        $ciphertext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                        $block = substr($plaintext, $i, $block_size);
                        $key = $this->encryptBlock($xor);
                        Strings::increment_str($xor);
                        $ciphertext.= $block ^ $key;
                    }
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                    if ($start = strlen($plaintext) % $block_size) {
                        $buffer['ciphertext'] = substr($key, $start) . $buffer['ciphertext'];
                    }
                }
                break;
            case self::MODE_CFB:
                // cfb loosely routines inspired by openssl's:
                // {@link http://cvs.openssl.org/fileview?f=openssl/crypto/modes/cfb128.c&v=1.3.2.2.2.1}
                if ($this->continuousBuffer) {
                    $iv = &$this->encryptIV;
                    $pos = &$buffer['pos'];
                } else {
                    $iv = $this->encryptIV;
                    $pos = 0;
                }
                $len = strlen($plaintext);
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = $block_size - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $blocksize
                    $ciphertext = substr($iv, $orig_pos) ^ $plaintext;
                    $iv = substr_replace($iv, $ciphertext, $orig_pos, $i);
                }
                while ($len >= $block_size) {
                    $iv = $this->encryptBlock($iv) ^ substr($plaintext, $i, $block_size);
                    $ciphertext.= $iv;
                    $len-= $block_size;
                    $i+= $block_size;
                }
                if ($len) {
                    $iv = $this->encryptBlock($iv);
                    $block = $iv ^ substr($plaintext, $i);
                    $iv = substr_replace($iv, $block, 0, $len);
                    $ciphertext.= $block;
                    $pos = $len;
                }
                break;
            case self::MODE_CFB8:
                $ciphertext = '';
                $len = strlen($plaintext);
                $iv = $this->encryptIV;

                for ($i = 0; $i < $len; ++$i) {
                    $ciphertext .= ($c = $plaintext[$i] ^ $this->encryptBlock($iv));
                    $iv = substr($iv, 1) . $c;
                }

                if ($this->continuousBuffer) {
                    if ($len >= $block_size) {
                        $this->encryptIV = substr($ciphertext, -$block_size);
                    } else {
                        $this->encryptIV = substr($this->encryptIV, $len - $block_size) . substr($ciphertext, -$len);
                    }
                }
                break;
            case self::MODE_OFB:
                $xor = $this->encryptIV;
                if (strlen($buffer['xor'])) {
                    for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                        $block = substr($plaintext, $i, $block_size);
                        if (strlen($block) > strlen($buffer['xor'])) {
                            $xor = $this->encryptBlock($xor);
                            $buffer['xor'].= $xor;
                        }
                        $key = Strings::shift($buffer['xor'], $block_size);
                        $ciphertext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                        $xor = $this->encryptBlock($xor);
                        $ciphertext.= substr($plaintext, $i, $block_size) ^ $xor;
                    }
                    $key = $xor;
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                    if ($start = strlen($plaintext) % $block_size) {
                        $buffer['xor'] = substr($key, $start) . $buffer['xor'];
                    }
                }
                break;
            case self::MODE_STREAM:
                $ciphertext = $this->encryptBlock($plaintext);
                break;
        }

        return $ciphertext;
    }

    /**
     * Decrypts a message.
     *
     * If strlen($ciphertext) is not a multiple of the block size, null bytes will be added to the end of the string until
     * it is.
     *
     * {@internal Could, but not must, extend by the child Crypt_* class}
     *
     * @see self::encrypt()
     * @access public
     * @param string $ciphertext
     * @return string $plaintext
     * @throws \LengthException if we're inside a block cipher and the ciphertext length is not a multiple of the block size
     */
    public function decrypt($ciphertext)
    {
        if ($this->paddable && strlen($ciphertext) % $this->block_size) {
            throw new \LengthException('The ciphertext length (' . strlen($ciphertext) . ') needs to be a multiple of the block size (' . $this->block_size . ')');
        }

        $this->setup();

        if ($this->mode == self::MODE_GCM || isset($this->poly1305Key)) {
            if ($this->oldtag === false) {
                throw new InsufficientSetupException('Authentication Tag has not been set');
            }

            if (isset($this->poly1305Key)) {
                $newtag = $this->poly1305($ciphertext);
            } else {
                $oldIV = $this->iv;
                Strings::increment_str($this->iv);
                $cipher = new static('ctr');
                $cipher->setKey($this->key);
                $cipher->setIV($this->iv);
                $plaintext = $cipher->decrypt($ciphertext);

                $s = $this->ghash(
                    self::nullPad128($this->aad) .
                    self::nullPad128($ciphertext) .
                    self::len64($this->aad) .
                    self::len64($ciphertext)
                );
                $cipher->encryptIV = $this->iv = $this->encryptIV = $this->decryptIV = $oldIV;
                $newtag = $cipher->encrypt($s);
            }
            if ($this->oldtag != substr($newtag, 0, strlen($newtag))) {
                $cipher = clone $this;
                unset($cipher->poly1305Key);
                $this->usePoly1305 = false;
                $plaintext = $cipher->decrypt($ciphertext);
                $this->oldtag = false;
                throw new BadDecryptionException('Derived authentication tag and supplied authentication tag do not match');
            }
            $this->oldtag = false;
            return $plaintext;
        }

        if ($this->engine === self::ENGINE_OPENSSL) {
            switch ($this->mode) {
                case self::MODE_STREAM:
                    $plaintext = openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
                    break;
                case self::MODE_ECB:
                    $plaintext = openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
                    break;
                case self::MODE_CBC:
                    $offset = $this->block_size;
                    $plaintext = openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $this->decryptIV);
                    if ($this->continuousBuffer) {
                        $this->decryptIV = substr($ciphertext, -$offset, $this->block_size);
                    }
                    break;
                case self::MODE_CTR:
                    $plaintext = $this->openssl_ctr_process($ciphertext, $this->decryptIV, $this->debuffer);
                    break;
                case self::MODE_CFB:
                    // cfb loosely routines inspired by openssl's:
                    // {@link http://cvs.openssl.org/fileview?f=openssl/crypto/modes/cfb128.c&v=1.3.2.2.2.1}
                    $plaintext = '';
                    if ($this->continuousBuffer) {
                        $iv = &$this->decryptIV;
                        $pos = &$this->buffer['pos'];
                    } else {
                        $iv = $this->decryptIV;
                        $pos = 0;
                    }
                    $len = strlen($ciphertext);
                    $i = 0;
                    if ($pos) {
                        $orig_pos = $pos;
                        $max = $this->block_size - $pos;
                        if ($len >= $max) {
                            $i = $max;
                            $len-= $max;
                            $pos = 0;
                        } else {
                            $i = $len;
                            $pos+= $len;
                            $len = 0;
                        }
                        // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $this->blocksize
                        $plaintext = substr($iv, $orig_pos) ^ $ciphertext;
                        $iv = substr_replace($iv, substr($ciphertext, 0, $i), $orig_pos, $i);
                        $ciphertext = substr($ciphertext, $i);
                    }
                    $overflow = $len % $this->block_size;
                    if ($overflow) {
                        $plaintext.= openssl_decrypt(substr($ciphertext, 0, -$overflow), $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
                        if ($len - $overflow) {
                            $iv = substr($ciphertext, -$overflow - $this->block_size, -$overflow);
                        }
                        $iv = openssl_encrypt(str_repeat("\0", $this->block_size), $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
                        $plaintext.= $iv ^ substr($ciphertext, -$overflow);
                        $iv = substr_replace($iv, substr($ciphertext, -$overflow), 0, $overflow);
                        $pos = $overflow;
                    } elseif ($len) {
                        $plaintext.= openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
                        $iv = substr($ciphertext, -$this->block_size);
                    }
                    break;
                case self::MODE_CFB8:
                    $plaintext = openssl_decrypt($ciphertext, $this->cipher_name_openssl, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $this->decryptIV);
                    if ($this->continuousBuffer) {
                        if (($len = strlen($ciphertext)) >= $this->block_size) {
                            $this->decryptIV = substr($ciphertext, -$this->block_size);
                        } else {
                            $this->decryptIV = substr($this->decryptIV, $len - $this->block_size) . substr($ciphertext, -$len);
                        }
                    }
                    break;
                case self::MODE_OFB:
                    $plaintext = $this->openssl_ofb_process($ciphertext, $this->decryptIV, $this->debuffer);
            }

            return $this->paddable ? $this->unpad($plaintext) : $plaintext;
        }

        if ($this->engine === self::ENGINE_MCRYPT) {
            $block_size = $this->block_size;
            if ($this->dechanged) {
                @mcrypt_generic_init($this->demcrypt, $this->key, $this->getIV($this->decryptIV));
                $this->dechanged = false;
            }

            if ($this->mode == self::MODE_CFB && $this->continuousBuffer) {
                $iv = &$this->decryptIV;
                $pos = &$this->debuffer['pos'];
                $len = strlen($ciphertext);
                $plaintext = '';
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = $block_size - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $blocksize
                    $plaintext = substr($iv, $orig_pos) ^ $ciphertext;
                    $iv = substr_replace($iv, substr($ciphertext, 0, $i), $orig_pos, $i);
                }
                if ($len >= $block_size) {
                    $cb = substr($ciphertext, $i, $len - $len % $block_size);
                    $plaintext.= @mcrypt_generic($this->ecb, $iv . $cb) ^ $cb;
                    $iv = substr($cb, -$block_size);
                    $len%= $block_size;
                }
                if ($len) {
                    $iv = @mcrypt_generic($this->ecb, $iv);
                    $plaintext.= $iv ^ substr($ciphertext, -$len);
                    $iv = substr_replace($iv, substr($ciphertext, -$len), 0, $len);
                    $pos = $len;
                }

                return $plaintext;
            }

            $plaintext = @mdecrypt_generic($this->demcrypt, $ciphertext);

            if (!$this->continuousBuffer) {
                @mcrypt_generic_init($this->demcrypt, $this->key, $this->getIV($this->decryptIV));
            }

            return $this->paddable ? $this->unpad($plaintext) : $plaintext;
        }

        if ($this->engine === self::ENGINE_EVAL) {
            $inline = $this->inline_crypt;
            return $inline('decrypt', $ciphertext);
        }

        $block_size = $this->block_size;

        $buffer = &$this->debuffer;
        $plaintext = '';
        switch ($this->mode) {
            case self::MODE_ECB:
                for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                    $plaintext.= $this->decryptBlock(substr($ciphertext, $i, $block_size));
                }
                break;
            case self::MODE_CBC:
                $xor = $this->decryptIV;
                for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                    $block = substr($ciphertext, $i, $block_size);
                    $plaintext.= $this->decryptBlock($block) ^ $xor;
                    $xor = $block;
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                }
                break;
            case self::MODE_CTR:
                $xor = $this->decryptIV;
                if (strlen($buffer['ciphertext'])) {
                    for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                        $block = substr($ciphertext, $i, $block_size);
                        if (strlen($block) > strlen($buffer['ciphertext'])) {
                            $buffer['ciphertext'].= $this->encryptBlock($xor);
                        }
                        Strings::increment_str($xor);
                        $key = Strings::shift($buffer['ciphertext'], $block_size);
                        $plaintext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                        $block = substr($ciphertext, $i, $block_size);
                        $key = $this->encryptBlock($xor);
                        Strings::increment_str($xor);
                        $plaintext.= $block ^ $key;
                    }
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                    if ($start = strlen($ciphertext) % $block_size) {
                        $buffer['ciphertext'] = substr($key, $start) . $buffer['ciphertext'];
                    }
                }
                break;
            case self::MODE_CFB:
                if ($this->continuousBuffer) {
                    $iv = &$this->decryptIV;
                    $pos = &$buffer['pos'];
                } else {
                    $iv = $this->decryptIV;
                    $pos = 0;
                }
                $len = strlen($ciphertext);
                $i = 0;
                if ($pos) {
                    $orig_pos = $pos;
                    $max = $block_size - $pos;
                    if ($len >= $max) {
                        $i = $max;
                        $len-= $max;
                        $pos = 0;
                    } else {
                        $i = $len;
                        $pos+= $len;
                        $len = 0;
                    }
                    // ie. $i = min($max, $len), $len-= $i, $pos+= $i, $pos%= $blocksize
                    $plaintext = substr($iv, $orig_pos) ^ $ciphertext;
                    $iv = substr_replace($iv, substr($ciphertext, 0, $i), $orig_pos, $i);
                }
                while ($len >= $block_size) {
                    $iv = $this->encryptBlock($iv);
                    $cb = substr($ciphertext, $i, $block_size);
                    $plaintext.= $iv ^ $cb;
                    $iv = $cb;
                    $len-= $block_size;
                    $i+= $block_size;
                }
                if ($len) {
                    $iv = $this->encryptBlock($iv);
                    $plaintext.= $iv ^ substr($ciphertext, $i);
                    $iv = substr_replace($iv, substr($ciphertext, $i), 0, $len);
                    $pos = $len;
                }
                break;
            case self::MODE_CFB8:
                $plaintext = '';
                $len = strlen($ciphertext);
                $iv = $this->decryptIV;

                for ($i = 0; $i < $len; ++$i) {
                    $plaintext .= $ciphertext[$i] ^ $this->encryptBlock($iv);
                    $iv = substr($iv, 1) . $ciphertext[$i];
                }

                if ($this->continuousBuffer) {
                    if ($len >= $block_size) {
                        $this->decryptIV = substr($ciphertext, -$block_size);
                    } else {
                        $this->decryptIV = substr($this->decryptIV, $len - $block_size) . substr($ciphertext, -$len);
                    }
                }
                break;
            case self::MODE_OFB:
                $xor = $this->decryptIV;
                if (strlen($buffer['xor'])) {
                    for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                        $block = substr($ciphertext, $i, $block_size);
                        if (strlen($block) > strlen($buffer['xor'])) {
                            $xor = $this->encryptBlock($xor);
                            $buffer['xor'].= $xor;
                        }
                        $key = Strings::shift($buffer['xor'], $block_size);
                        $plaintext.= $block ^ $key;
                    }
                } else {
                    for ($i = 0; $i < strlen($ciphertext); $i+=$block_size) {
                        $xor = $this->encryptBlock($xor);
                        $plaintext.= substr($ciphertext, $i, $block_size) ^ $xor;
                    }
                    $key = $xor;
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                    if ($start = strlen($ciphertext) % $block_size) {
                        $buffer['xor'] = substr($key, $start) . $buffer['xor'];
                    }
                }
                break;
            case self::MODE_STREAM:
                $plaintext = $this->decryptBlock($ciphertext);
                break;
        }
        return $this->paddable ? $this->unpad($plaintext) : $plaintext;
    }

    /**
     * Get the authentication tag
     *
     * Only used in GCM or Poly1305 mode
     *
     * @see self::encrypt()
     * @param int $length optional
     * @return string
     * @access public
     * @throws \LengthException if $length isn't of a sufficient length
     * @throws \RuntimeException if GCM mode isn't being used
     */
    public function getTag($length = 16)
    {
        if ($this->mode != self::MODE_GCM && !$this->usePoly1305) {
            throw new \BadMethodCallException('Authentication tags are only utilized in GCM mode or with Poly1305');
        }

        if ($this->newtag === false) {
            throw new \BadMethodCallException('A tag can only be returned after a round of encryption has been performed');
        }

        // the tag is 128-bits. it can't be greater than 16 bytes because that's bigger than the tag is. if it
        // were 0 you might as well be doing CTR and less than 4 provides minimal security that could be trivially
        // easily brute forced.
        // see https://nvlpubs.nist.gov/nistpubs/Legacy/SP/nistspecialpublication800-38d.pdf#page=36
        // for more info
        if ($length < 4 || $length > 16) {
            throw new \LengthException('The authentication tag must be between 4 and 16 bytes long');
        }

        return $length == 16 ?
            $this->newtag :
            substr($this->newtag, 0, $length);
    }

    /**
     * Sets the authentication tag
     *
     * Only used in GCM mode
     *
     * @see self::decrypt()
     * @param string $tag
     * @access public
     * @throws \LengthException if $length isn't of a sufficient length
     * @throws \RuntimeException if GCM mode isn't being used
     */
    public function setTag($tag)
    {
        if ($this->usePoly1305 && !isset($this->poly1305Key) && method_exists($this, 'createPoly1305Key')) {
            $this->createPoly1305Key();
        }

        if ($this->mode != self::MODE_GCM && !$this->usePoly1305) {
            throw new \BadMethodCallException('Authentication tags are only utilized in GCM mode or with Poly1305');
        }

        $length = strlen($tag);
        if ($length < 4 || $length > 16) {
            throw new \LengthException('The authentication tag must be between 4 and 16 bytes long');
        }
        $this->oldtag = $tag;
    }

    /**
     * Get the IV
     *
     * mcrypt requires an IV even if ECB is used
     *
     * @see self::encrypt()
     * @see self::decrypt()
     * @param string $iv
     * @return string
     * @access private
     */
    protected function getIV($iv)
    {
        return $this->mode == self::MODE_ECB ? str_repeat("\0", $this->block_size) : $iv;
    }

    /**
     * OpenSSL CTR Processor
     *
     * PHP's OpenSSL bindings do not operate in continuous mode so we'll wrap around it. Since the keystream
     * for CTR is the same for both encrypting and decrypting this function is re-used by both SymmetricKey::encrypt()
     * and SymmetricKey::decrypt(). Also, OpenSSL doesn't implement CTR for all of it's symmetric ciphers so this
     * function will emulate CTR with ECB when necessary.
     *
     * @see self::encrypt()
     * @see self::decrypt()
     * @param string $plaintext
     * @param string $encryptIV
     * @param array $buffer
     * @return string
     * @access private
     */
    private function openssl_ctr_process($plaintext, &$encryptIV, &$buffer)
    {
        $ciphertext = '';

        $block_size = $this->block_size;
        $key = $this->key;

        if ($this->openssl_emulate_ctr) {
            $xor = $encryptIV;
            if (strlen($buffer['ciphertext'])) {
                for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                    $block = substr($plaintext, $i, $block_size);
                    if (strlen($block) > strlen($buffer['ciphertext'])) {
                        $buffer['ciphertext'].= openssl_encrypt($xor, static::$cipher_name_openssl_ecb, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
                    }
                    Strings::increment_str($xor);
                    $otp = Strings::shift($buffer['ciphertext'], $block_size);
                    $ciphertext.= $block ^ $otp;
                }
            } else {
                for ($i = 0; $i < strlen($plaintext); $i+=$block_size) {
                    $block = substr($plaintext, $i, $block_size);
                    $otp = openssl_encrypt($xor, static::$cipher_name_openssl_ecb, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
                    Strings::increment_str($xor);
                    $ciphertext.= $block ^ $otp;
                }
            }
            if ($this->continuousBuffer) {
                $encryptIV = $xor;
                if ($start = strlen($plaintext) % $block_size) {
                    $buffer['ciphertext'] = substr($key, $start) . $buffer['ciphertext'];
                }
            }

            return $ciphertext;
        }

        if (strlen($buffer['ciphertext'])) {
            $ciphertext = $plaintext ^ Strings::shift($buffer['ciphertext'], strlen($plaintext));
            $plaintext = substr($plaintext, strlen($ciphertext));

            if (!strlen($plaintext)) {
                return $ciphertext;
            }
        }

        $overflow = strlen($plaintext) % $block_size;
        if ($overflow) {
            $plaintext2 = Strings::pop($plaintext, $overflow); // ie. trim $plaintext to a multiple of $block_size and put rest of $plaintext in $plaintext2
            $encrypted = openssl_encrypt($plaintext . str_repeat("\0", $block_size), $this->cipher_name_openssl, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $encryptIV);
            $temp = Strings::pop($encrypted, $block_size);
            $ciphertext.= $encrypted . ($plaintext2 ^ $temp);
            if ($this->continuousBuffer) {
                $buffer['ciphertext'] = substr($temp, $overflow);
                $encryptIV = $temp;
            }
        } elseif (!strlen($buffer['ciphertext'])) {
            $ciphertext.= openssl_encrypt($plaintext . str_repeat("\0", $block_size), $this->cipher_name_openssl, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $encryptIV);
            $temp = Strings::pop($ciphertext, $block_size);
            if ($this->continuousBuffer) {
                $encryptIV = $temp;
            }
        }
        if ($this->continuousBuffer) {
            $encryptIV = openssl_decrypt($encryptIV, static::$cipher_name_openssl_ecb, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
            if ($overflow) {
                Strings::increment_str($encryptIV);
            }
        }

        return $ciphertext;
    }

    /**
     * OpenSSL OFB Processor
     *
     * PHP's OpenSSL bindings do not operate in continuous mode so we'll wrap around it. Since the keystream
     * for OFB is the same for both encrypting and decrypting this function is re-used by both SymmetricKey::encrypt()
     * and SymmetricKey::decrypt().
     *
     * @see self::encrypt()
     * @see self::decrypt()
     * @param string $plaintext
     * @param string $encryptIV
     * @param array $buffer
     * @return string
     * @access private
     */
    private function openssl_ofb_process($plaintext, &$encryptIV, &$buffer)
    {
        if (strlen($buffer['xor'])) {
            $ciphertext = $plaintext ^ $buffer['xor'];
            $buffer['xor'] = substr($buffer['xor'], strlen($ciphertext));
            $plaintext = substr($plaintext, strlen($ciphertext));
        } else {
            $ciphertext = '';
        }

        $block_size = $this->block_size;

        $len = strlen($plaintext);
        $key = $this->key;
        $overflow = $len % $block_size;

        if (strlen($plaintext)) {
            if ($overflow) {
                $ciphertext.= openssl_encrypt(substr($plaintext, 0, -$overflow) . str_repeat("\0", $block_size), $this->cipher_name_openssl, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $encryptIV);
                $xor = Strings::pop($ciphertext, $block_size);
                if ($this->continuousBuffer) {
                    $encryptIV = $xor;
                }
                $ciphertext.= Strings::shift($xor, $overflow) ^ substr($plaintext, -$overflow);
                if ($this->continuousBuffer) {
                    $buffer['xor'] = $xor;
                }
            } else {
                $ciphertext = openssl_encrypt($plaintext, $this->cipher_name_openssl, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $encryptIV);
                if ($this->continuousBuffer) {
                    $encryptIV = substr($ciphertext, -$block_size) ^ substr($plaintext, -$block_size);
                }
            }
        }

        return $ciphertext;
    }

    /**
     * phpseclib <-> OpenSSL Mode Mapper
     *
     * May need to be overwritten by classes extending this one in some cases
     *
     * @return string
     * @access private
     */
    protected function openssl_translate_mode()
    {
        switch ($this->mode) {
            case self::MODE_ECB:
                return 'ecb';
            case self::MODE_CBC:
                return 'cbc';
            case self::MODE_CTR:
            case self::MODE_GCM:
                return 'ctr';
            case self::MODE_CFB:
                return 'cfb';
            case self::MODE_CFB8:
                return 'cfb8';
            case self::MODE_OFB:
                return 'ofb';
        }
    }

    /**
     * Pad "packets".
     *
     * Block ciphers working by encrypting between their specified [$this->]block_size at a time
     * If you ever need to encrypt or decrypt something that isn't of the proper length, it becomes necessary to
     * pad the input so that it is of the proper length.
     *
     * Padding is enabled by default.  Sometimes, however, it is undesirable to pad strings.  Such is the case in SSH,
     * where "packets" are padded with random bytes before being encrypted.  Unpad these packets and you risk stripping
     * away characters that shouldn't be stripped away. (SSH knows how many bytes are added because the length is
     * transmitted separately)
     *
     * @see self::disablePadding()
     * @access public
     */
    public function enablePadding()
    {
        $this->padding = true;
    }

    /**
     * Do not pad packets.
     *
     * @see self::enablePadding()
     * @access public
     */
    public function disablePadding()
    {
        $this->padding = false;
    }

    /**
     * Treat consecutive "packets" as if they are a continuous buffer.
     *
     * Say you have a 32-byte plaintext $plaintext.  Using the default behavior, the two following code snippets
     * will yield different outputs:
     *
     * <code>
     *    echo $rijndael->encrypt(substr($plaintext,  0, 16));
     *    echo $rijndael->encrypt(substr($plaintext, 16, 16));
     * </code>
     * <code>
     *    echo $rijndael->encrypt($plaintext);
     * </code>
     *
     * The solution is to enable the continuous buffer.  Although this will resolve the above discrepancy, it creates
     * another, as demonstrated with the following:
     *
     * <code>
     *    $rijndael->encrypt(substr($plaintext, 0, 16));
     *    echo $rijndael->decrypt($rijndael->encrypt(substr($plaintext, 16, 16)));
     * </code>
     * <code>
     *    echo $rijndael->decrypt($rijndael->encrypt(substr($plaintext, 16, 16)));
     * </code>
     *
     * With the continuous buffer disabled, these would yield the same output.  With it enabled, they yield different
     * outputs.  The reason is due to the fact that the initialization vector's change after every encryption /
     * decryption round when the continuous buffer is enabled.  When it's disabled, they remain constant.
     *
     * Put another way, when the continuous buffer is enabled, the state of the \phpseclib3\Crypt\*() object changes after each
     * encryption / decryption round, whereas otherwise, it'd remain constant.  For this reason, it's recommended that
     * continuous buffers not be used.  They do offer better security and are, in fact, sometimes required (SSH uses them),
     * however, they are also less intuitive and more likely to cause you problems.
     *
     * {@internal Could, but not must, extend by the child Crypt_* class}
     *
     * @see self::disableContinuousBuffer()
     * @access public
     */
    public function enableContinuousBuffer()
    {
        if ($this->mode == self::MODE_ECB) {
            return;
        }

        if ($this->mode == self::MODE_GCM) {
            throw new \BadMethodCallException('This mode does not run in continuous mode');
        }

        $this->continuousBuffer = true;

        $this->setEngine();
    }

    /**
     * Treat consecutive packets as if they are a discontinuous buffer.
     *
     * The default behavior.
     *
     * {@internal Could, but not must, extend by the child Crypt_* class}
     *
     * @see self::enableContinuousBuffer()
     * @access public
     */
    public function disableContinuousBuffer()
    {
        if ($this->mode == self::MODE_ECB) {
            return;
        }
        if (!$this->continuousBuffer) {
            return;
        }

        $this->continuousBuffer = false;

        $this->setEngine();
    }

    /**
     * Test for engine validity
     *
     * @see self::__construct()
     * @param int $engine
     * @access private
     * @return bool
     */
    protected function isValidEngineHelper($engine)
    {
        switch ($engine) {
            case self::ENGINE_OPENSSL:
                $this->openssl_emulate_ctr = false;
                $result = $this->cipher_name_openssl &&
                          extension_loaded('openssl');
                if (!$result) {
                    return false;
                }

                $methods = openssl_get_cipher_methods();
                if (in_array($this->cipher_name_openssl, $methods)) {
                    return true;
                }
                // not all of openssl's symmetric cipher's support ctr. for those
                // that don't we'll emulate it
                switch ($this->mode) {
                    case self::MODE_CTR:
                        if (in_array(static::$cipher_name_openssl_ecb, $methods)) {
                            $this->openssl_emulate_ctr = true;
                            return true;
                        }
                }
                return false;
            case self::ENGINE_MCRYPT:
                return $this->cipher_name_mcrypt &&
                       extension_loaded('mcrypt') &&
                       in_array($this->cipher_name_mcrypt, @mcrypt_list_algorithms());
            case self::ENGINE_EVAL:
                return method_exists($this, 'setupInlineCrypt');
            case self::ENGINE_INTERNAL:
                return true;
        }

        return false;
    }

    /**
     * Test for engine validity
     *
     * @see self::__construct()
     * @param string $engine
     * @access public
     * @return bool
     */
    public function isValidEngine($engine)
    {
        static $reverseMap;
        if (!isset($reverseMap)) {
            $reverseMap = array_map('strtolower', self::ENGINE_MAP);
            $reverseMap = array_flip($reverseMap);
        }
        $engine = strtolower($engine);
        if (!isset($reverseMap[$engine])) {
            return false;
        }

        return $this->isValidEngineHelper($reverseMap[$engine]);
    }

    /**
     * Sets the preferred crypt engine
     *
     * Currently, $engine could be:
     *
     * - libsodium[very fast]
     *
     * - OpenSSL  [very fast]
     *
     * - mcrypt   [fast]
     *
     * - Eval     [slow]
     *
     * - PHP      [slowest]
     *
     * If the preferred crypt engine is not available the fastest available one will be used
     *
     * @see self::__construct()
     * @param string $engine
     * @access public
     */
    public function setPreferredEngine($engine)
    {
        static $reverseMap;
        if (!isset($reverseMap)) {
            $reverseMap = array_map('strtolower', self::ENGINE_MAP);
            $reverseMap = array_flip($reverseMap);
        }
        $engine = strtolower($engine);
        $this->preferredEngine = isset($reverseMap[$engine]) ? $reverseMap[$engine] : self::ENGINE_LIBSODIUM;

        $this->setEngine();
    }

    /**
     * Returns the engine currently being utilized
     *
     * @see self::setEngine()
     * @access public
     */
    public function getEngine()
    {
        return self::ENGINE_MAP[$this->engine];
    }

    /**
     * Sets the engine as appropriate
     *
     * @see self::__construct()
     * @access private
     */
    protected function setEngine()
    {
        $this->engine = null;

        $candidateEngines = [
            self::ENGINE_LIBSODIUM,
            self::ENGINE_OPENSSL_GCM,
            self::ENGINE_OPENSSL,
            self::ENGINE_MCRYPT,
            self::ENGINE_EVAL
        ];
        if (isset($this->preferredEngine)) {
            $temp = [$this->preferredEngine];
            $candidateEngines = array_merge(
                $temp,
                array_diff($candidateEngines, $temp)
            );
        }
        foreach ($candidateEngines as $engine) {
            if ($this->isValidEngineHelper($engine)) {
                $this->engine = $engine;
                break;
            }
        }
        if (!$this->engine) {
            $this->engine = self::ENGINE_INTERNAL;
        }

        if ($this->engine != self::ENGINE_MCRYPT && $this->enmcrypt) {
            // Closing the current mcrypt resource(s). _mcryptSetup() will, if needed,
            // (re)open them with the module named in $this->cipher_name_mcrypt
            @mcrypt_module_close($this->enmcrypt);
            @mcrypt_module_close($this->demcrypt);
            $this->enmcrypt = null;
            $this->demcrypt = null;

            if ($this->ecb) {
                @mcrypt_module_close($this->ecb);
                $this->ecb = null;
            }
        }

        $this->changed = $this->nonIVChanged = true;
    }

    /**
     * Encrypts a block
     *
     * Note: Must be extended by the child \phpseclib3\Crypt\* class
     *
     * @access private
     * @param string $in
     * @return string
     */
    abstract protected function encryptBlock($in);

    /**
     * Decrypts a block
     *
     * Note: Must be extended by the child \phpseclib3\Crypt\* class
     *
     * @access private
     * @param string $in
     * @return string
     */
    abstract protected function decryptBlock($in);

    /**
     * Setup the key (expansion)
     *
     * Only used if $engine == self::ENGINE_INTERNAL
     *
     * Note: Must extend by the child \phpseclib3\Crypt\* class
     *
     * @see self::setup()
     * @access private
     */
    abstract protected function setupKey();

    /**
     * Setup the self::ENGINE_INTERNAL $engine
     *
     * (re)init, if necessary, the internal cipher $engine and flush all $buffers
     * Used (only) if $engine == self::ENGINE_INTERNAL
     *
     * _setup() will be called each time if $changed === true
     * typically this happens when using one or more of following public methods:
     *
     * - setKey()
     *
     * - setIV()
     *
     * - disableContinuousBuffer()
     *
     * - First run of encrypt() / decrypt() with no init-settings
     *
     * {@internal setup() is always called before en/decryption.}
     *
     * {@internal Could, but not must, extend by the child Crypt_* class}
     *
     * @see self::setKey()
     * @see self::setIV()
     * @see self::disableContinuousBuffer()
     * @access private
     */
    protected function setup()
    {
        if (!$this->changed) {
            return;
        }

        $this->changed = false;

        if ($this->usePoly1305 && !isset($this->poly1305Key) && method_exists($this, 'createPoly1305Key')) {
            $this->createPoly1305Key();
        }

        $this->enbuffer = $this->debuffer = ['ciphertext' => '', 'xor' => '', 'pos' => 0, 'enmcrypt_init' => true];
        //$this->newtag = $this->oldtag = false;

        if ($this->usesNonce()) {
            if ($this->nonce === false) {
                throw new InsufficientSetupException('No nonce has been defined');
            }
            if ($this->mode == self::MODE_GCM && !in_array($this->engine, [self::ENGINE_LIBSODIUM, self::ENGINE_OPENSSL_GCM])) {
                $this->setupGCM();
            }
        } else {
            $this->iv = $this->origIV;
        }

        if ($this->iv === false && !in_array($this->mode, [self::MODE_STREAM, self::MODE_ECB])) {
            if ($this->mode != self::MODE_GCM || !in_array($this->engine, [self::ENGINE_LIBSODIUM, self::ENGINE_OPENSSL_GCM])) {
                throw new InsufficientSetupException('No IV has been defined');
            }
        }

        if ($this->key === false) {
            throw new InsufficientSetupException('No key has been defined');
        }

        $this->encryptIV = $this->decryptIV = $this->iv;

        switch ($this->engine) {
            case self::ENGINE_MCRYPT:
                $this->enchanged = $this->dechanged = true;

                if (!isset($this->enmcrypt)) {
                    static $mcrypt_modes = [
                        self::MODE_CTR    => 'ctr',
                        self::MODE_ECB    => MCRYPT_MODE_ECB,
                        self::MODE_CBC    => MCRYPT_MODE_CBC,
                        self::MODE_CFB    => 'ncfb',
                        self::MODE_CFB8   => MCRYPT_MODE_CFB,
                        self::MODE_OFB    => MCRYPT_MODE_NOFB,
                        self::MODE_STREAM => MCRYPT_MODE_STREAM,
                    ];

                    $this->demcrypt = @mcrypt_module_open($this->cipher_name_mcrypt, '', $mcrypt_modes[$this->mode], '');
                    $this->enmcrypt = @mcrypt_module_open($this->cipher_name_mcrypt, '', $mcrypt_modes[$this->mode], '');

                    // we need the $ecb mcrypt resource (only) in MODE_CFB with enableContinuousBuffer()
                    // to workaround mcrypt's broken ncfb implementation in buffered mode
                    // see: {@link http://phpseclib.sourceforge.net/cfb-demo.phps}
                    if ($this->mode == self::MODE_CFB) {
                        $this->ecb = @mcrypt_module_open($this->cipher_name_mcrypt, '', MCRYPT_MODE_ECB, '');
                    }
                } // else should mcrypt_generic_deinit be called?

                if ($this->mode == self::MODE_CFB) {
                    @mcrypt_generic_init($this->ecb, $this->key, str_repeat("\0", $this->block_size));
                }
                break;
            case self::ENGINE_INTERNAL:
                $this->setupKey();
                break;
            case self::ENGINE_EVAL:
                if ($this->nonIVChanged) {
                    $this->setupKey();
                    $this->setupInlineCrypt();
                }
        }

        $this->nonIVChanged = false;
    }

    /**
     * Pads a string
     *
     * Pads a string using the RSA PKCS padding standards so that its length is a multiple of the blocksize.
     * $this->block_size - (strlen($text) % $this->block_size) bytes are added, each of which is equal to
     * chr($this->block_size - (strlen($text) % $this->block_size)
     *
     * If padding is disabled and $text is not a multiple of the blocksize, the string will be padded regardless
     * and padding will, hence forth, be enabled.
     *
     * @see self::unpad()
     * @param string $text
     * @throws \LengthException if padding is disabled and the plaintext's length is not a multiple of the block size
     * @access private
     * @return string
     */
    protected function pad($text)
    {
        $length = strlen($text);

        if (!$this->padding) {
            if ($length % $this->block_size == 0) {
                return $text;
            } else {
                throw new \LengthException("The plaintext's length ($length) is not a multiple of the block size ({$this->block_size}). Try enabling padding.");
            }
        }

        $pad = $this->block_size - ($length % $this->block_size);

        return str_pad($text, $length + $pad, chr($pad));
    }

    /**
     * Unpads a string.
     *
     * If padding is enabled and the reported padding length is invalid the encryption key will be assumed to be wrong
     * and false will be returned.
     *
     * @see self::pad()
     * @param string $text
     * @throws \LengthException if the ciphertext's length is not a multiple of the block size
     * @access private
     * @return string
     */
    protected function unpad($text)
    {
        if (!$this->padding) {
            return $text;
        }

        $length = ord($text[strlen($text) - 1]);

        if (!$length || $length > $this->block_size) {
            throw new BadDecryptionException("The ciphertext has an invalid padding length ($length) compared to the block size ({$this->block_size})");
        }

        return substr($text, 0, -$length);
    }

    /**
     * Setup the performance-optimized function for de/encrypt()
     *
     * Stores the created (or existing) callback function-name
     * in $this->inline_crypt
     *
     * Internally for phpseclib developers:
     *
     *     _setupInlineCrypt() would be called only if:
     *
     *     - $this->engine === self::ENGINE_EVAL
     *
     *     - each time on _setup(), after(!) _setupKey()
     *
     *
     *     This ensures that _setupInlineCrypt() has always a
     *     full ready2go initializated internal cipher $engine state
     *     where, for example, the keys already expanded,
     *     keys/block_size calculated and such.
     *
     *     It is, each time if called, the responsibility of _setupInlineCrypt():
     *
     *     - to set $this->inline_crypt to a valid and fully working callback function
     *       as a (faster) replacement for encrypt() / decrypt()
     *
     *     - NOT to create unlimited callback functions (for memory reasons!)
     *       no matter how often _setupInlineCrypt() would be called. At some
     *       point of amount they must be generic re-useable.
     *
     *     - the code of _setupInlineCrypt() it self,
     *       and the generated callback code,
     *       must be, in following order:
     *       - 100% safe
     *       - 100% compatible to encrypt()/decrypt()
     *       - using only php5+ features/lang-constructs/php-extensions if
     *         compatibility (down to php4) or fallback is provided
     *       - readable/maintainable/understandable/commented and... not-cryptic-styled-code :-)
     *       - >= 10% faster than encrypt()/decrypt() [which is, by the way,
     *         the reason for the existence of _setupInlineCrypt() :-)]
     *       - memory-nice
     *       - short (as good as possible)
     *
     * Note: - _setupInlineCrypt() is using _createInlineCryptFunction() to create the full callback function code.
     *       - In case of using inline crypting, _setupInlineCrypt() must extend by the child \phpseclib3\Crypt\* class.
     *       - The following variable names are reserved:
     *         - $_*  (all variable names prefixed with an underscore)
     *         - $self (object reference to it self. Do not use $this, but $self instead)
     *         - $in (the content of $in has to en/decrypt by the generated code)
     *       - The callback function should not use the 'return' statement, but en/decrypt'ing the content of $in only
     *
     * {@internal If a Crypt_* class providing inline crypting it must extend _setupInlineCrypt()}
     *
     * @see self::setup()
     * @see self::createInlineCryptFunction()
     * @see self::encrypt()
     * @see self::decrypt()
     * @access private
     */
    //protected function setupInlineCrypt();

    /**
     * Creates the performance-optimized function for en/decrypt()
     *
     * Internally for phpseclib developers:
     *
     *    _createInlineCryptFunction():
     *
     *    - merge the $cipher_code [setup'ed by _setupInlineCrypt()]
     *      with the current [$this->]mode of operation code
     *
     *    - create the $inline function, which called by encrypt() / decrypt()
     *      as its replacement to speed up the en/decryption operations.
     *
     *    - return the name of the created $inline callback function
     *
     *    - used to speed up en/decryption
     *
     *
     *
     *    The main reason why can speed up things [up to 50%] this way are:
     *
     *    - using variables more effective then regular.
     *      (ie no use of expensive arrays but integers $k_0, $k_1 ...
     *      or even, for example, the pure $key[] values hardcoded)
     *
     *    - avoiding 1000's of function calls of ie _encryptBlock()
     *      but inlining the crypt operations.
     *      in the mode of operation for() loop.
     *
     *    - full loop unroll the (sometimes key-dependent) rounds
     *      avoiding this way ++$i counters and runtime-if's etc...
     *
     *    The basic code architectur of the generated $inline en/decrypt()
     *    lambda function, in pseudo php, is:
     *
     *    <code>
     *    +----------------------------------------------------------------------------------------------+
     *    | callback $inline = create_function:                                                          |
     *    | lambda_function_0001_crypt_ECB($action, $text)                                               |
     *    | {                                                                                            |
     *    |     INSERT PHP CODE OF:                                                                      |
     *    |     $cipher_code['init_crypt'];                  // general init code.                       |
     *    |                                                  // ie: $sbox'es declarations used for       |
     *    |                                                  //     encrypt and decrypt'ing.             |
     *    |                                                                                              |
     *    |     switch ($action) {                                                                       |
     *    |         case 'encrypt':                                                                      |
     *    |             INSERT PHP CODE OF:                                                              |
     *    |             $cipher_code['init_encrypt'];       // encrypt sepcific init code.               |
     *    |                                                    ie: specified $key or $box                |
     *    |                                                        declarations for encrypt'ing.         |
     *    |                                                                                              |
     *    |             foreach ($ciphertext) {                                                          |
     *    |                 $in = $block_size of $ciphertext;                                            |
     *    |                                                                                              |
     *    |                 INSERT PHP CODE OF:                                                          |
     *    |                 $cipher_code['encrypt_block'];  // encrypt's (string) $in, which is always:  |
     *    |                                                 // strlen($in) == $this->block_size          |
     *    |                                                 // here comes the cipher algorithm in action |
     *    |                                                 // for encryption.                           |
     *    |                                                 // $cipher_code['encrypt_block'] has to      |
     *    |                                                 // encrypt the content of the $in variable   |
     *    |                                                                                              |
     *    |                 $plaintext .= $in;                                                           |
     *    |             }                                                                                |
     *    |             return $plaintext;                                                               |
     *    |                                                                                              |
     *    |         case 'decrypt':                                                                      |
     *    |             INSERT PHP CODE OF:                                                              |
     *    |             $cipher_code['init_decrypt'];       // decrypt sepcific init code                |
     *    |                                                    ie: specified $key or $box                |
     *    |                                                        declarations for decrypt'ing.         |
     *    |             foreach ($plaintext) {                                                           |
     *    |                 $in = $block_size of $plaintext;                                             |
     *    |                                                                                              |
     *    |                 INSERT PHP CODE OF:                                                          |
     *    |                 $cipher_code['decrypt_block'];  // decrypt's (string) $in, which is always   |
     *    |                                                 // strlen($in) == $this->block_size          |
     *    |                                                 // here comes the cipher algorithm in action |
     *    |                                                 // for decryption.                           |
     *    |                                                 // $cipher_code['decrypt_block'] has to      |
     *    |                                                 // decrypt the content of the $in variable   |
     *    |                 $ciphertext .= $in;                                                          |
     *    |             }                                                                                |
     *    |             return $ciphertext;                                                              |
     *    |     }                                                                                        |
     *    | }                                                                                            |
     *    +----------------------------------------------------------------------------------------------+
     *    </code>
     *
     *    See also the \phpseclib3\Crypt\*::_setupInlineCrypt()'s for
     *    productive inline $cipher_code's how they works.
     *
     *    Structure of:
     *    <code>
     *    $cipher_code = [
     *        'init_crypt'    => (string) '', // optional
     *        'init_encrypt'  => (string) '', // optional
     *        'init_decrypt'  => (string) '', // optional
     *        'encrypt_block' => (string) '', // required
     *        'decrypt_block' => (string) ''  // required
     *    ];
     *    </code>
     *
     * @see self::setupInlineCrypt()
     * @see self::encrypt()
     * @see self::decrypt()
     * @param array $cipher_code
     * @access private
     * @return string (the name of the created callback function)
     */
    protected function createInlineCryptFunction($cipher_code)
    {
        $block_size = $this->block_size;

        // optional
        $init_crypt    = isset($cipher_code['init_crypt'])    ? $cipher_code['init_crypt']    : '';
        $init_encrypt  = isset($cipher_code['init_encrypt'])  ? $cipher_code['init_encrypt']  : '';
        $init_decrypt  = isset($cipher_code['init_decrypt'])  ? $cipher_code['init_decrypt']  : '';
        // required
        $encrypt_block = $cipher_code['encrypt_block'];
        $decrypt_block = $cipher_code['decrypt_block'];

        // Generating mode of operation inline code,
        // merged with the $cipher_code algorithm
        // for encrypt- and decryption.
        switch ($this->mode) {
            case self::MODE_ECB:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_plaintext_len = strlen($_text);

                    for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                        $in = substr($_text, $_i, '.$block_size.');
                        '.$encrypt_block.'
                        $_ciphertext.= $in;
                    }

                    return $_ciphertext;
                    ';

                $decrypt = $init_decrypt . '
                    $_plaintext = "";
                    $_text = str_pad($_text, strlen($_text) + ('.$block_size.' - strlen($_text) % '.$block_size.') % '.$block_size.', chr(0));
                    $_ciphertext_len = strlen($_text);

                    for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                        $in = substr($_text, $_i, '.$block_size.');
                        '.$decrypt_block.'
                        $_plaintext.= $in;
                    }

                    return $this->unpad($_plaintext);
                    ';
                break;
            case self::MODE_CTR:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_plaintext_len = strlen($_text);
                    $_xor = $this->encryptIV;
                    $_buffer = &$this->enbuffer;
                    if (strlen($_buffer["ciphertext"])) {
                        for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            if (strlen($_block) > strlen($_buffer["ciphertext"])) {
                                $in = $_xor;
                                '.$encrypt_block.'
                                \phpseclib3\Common\Functions\Strings::increment_str($_xor);
                                $_buffer["ciphertext"].= $in;
                            }
                            $_key = \phpseclib3\Common\Functions\Strings::shift($_buffer["ciphertext"], '.$block_size.');
                            $_ciphertext.= $_block ^ $_key;
                        }
                    } else {
                        for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            $in = $_xor;
                            '.$encrypt_block.'
                            \phpseclib3\Common\Functions\Strings::increment_str($_xor);
                            $_key = $in;
                            $_ciphertext.= $_block ^ $_key;
                        }
                    }
                    if ($this->continuousBuffer) {
                        $this->encryptIV = $_xor;
                        if ($_start = $_plaintext_len % '.$block_size.') {
                            $_buffer["ciphertext"] = substr($_key, $_start) . $_buffer["ciphertext"];
                        }
                    }

                    return $_ciphertext;
                ';

                $decrypt = $init_encrypt . '
                    $_plaintext = "";
                    $_ciphertext_len = strlen($_text);
                    $_xor = $this->decryptIV;
                    $_buffer = &$this->debuffer;

                    if (strlen($_buffer["ciphertext"])) {
                        for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            if (strlen($_block) > strlen($_buffer["ciphertext"])) {
                                $in = $_xor;
                                '.$encrypt_block.'
                                \phpseclib3\Common\Functions\Strings::increment_str($_xor);
                                $_buffer["ciphertext"].= $in;
                            }
                            $_key = \phpseclib3\Common\Functions\Strings::shift($_buffer["ciphertext"], '.$block_size.');
                            $_plaintext.= $_block ^ $_key;
                        }
                    } else {
                        for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            $in = $_xor;
                            '.$encrypt_block.'
                            \phpseclib3\Common\Functions\Strings::increment_str($_xor);
                            $_key = $in;
                            $_plaintext.= $_block ^ $_key;
                        }
                    }
                    if ($this->continuousBuffer) {
                        $this->decryptIV = $_xor;
                        if ($_start = $_ciphertext_len % '.$block_size.') {
                            $_buffer["ciphertext"] = substr($_key, $_start) . $_buffer["ciphertext"];
                        }
                    }

                    return $_plaintext;
                    ';
                break;
            case self::MODE_CFB:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_buffer = &$this->enbuffer;

                    if ($this->continuousBuffer) {
                        $_iv = &$this->encryptIV;
                        $_pos = &$_buffer["pos"];
                    } else {
                        $_iv = $this->encryptIV;
                        $_pos = 0;
                    }
                    $_len = strlen($_text);
                    $_i = 0;
                    if ($_pos) {
                        $_orig_pos = $_pos;
                        $_max = '.$block_size.' - $_pos;
                        if ($_len >= $_max) {
                            $_i = $_max;
                            $_len-= $_max;
                            $_pos = 0;
                        } else {
                            $_i = $_len;
                            $_pos+= $_len;
                            $_len = 0;
                        }
                        $_ciphertext = substr($_iv, $_orig_pos) ^ $_text;
                        $_iv = substr_replace($_iv, $_ciphertext, $_orig_pos, $_i);
                    }
                    while ($_len >= '.$block_size.') {
                        $in = $_iv;
                        '.$encrypt_block.';
                        $_iv = $in ^ substr($_text, $_i, '.$block_size.');
                        $_ciphertext.= $_iv;
                        $_len-= '.$block_size.';
                        $_i+= '.$block_size.';
                    }
                    if ($_len) {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_iv = $in;
                        $_block = $_iv ^ substr($_text, $_i);
                        $_iv = substr_replace($_iv, $_block, 0, $_len);
                        $_ciphertext.= $_block;
                        $_pos = $_len;
                    }
                    return $_ciphertext;
                ';

                $decrypt = $init_encrypt . '
                    $_plaintext = "";
                    $_buffer = &$this->debuffer;

                    if ($this->continuousBuffer) {
                        $_iv = &$this->decryptIV;
                        $_pos = &$_buffer["pos"];
                    } else {
                        $_iv = $this->decryptIV;
                        $_pos = 0;
                    }
                    $_len = strlen($_text);
                    $_i = 0;
                    if ($_pos) {
                        $_orig_pos = $_pos;
                        $_max = '.$block_size.' - $_pos;
                        if ($_len >= $_max) {
                            $_i = $_max;
                            $_len-= $_max;
                            $_pos = 0;
                        } else {
                            $_i = $_len;
                            $_pos+= $_len;
                            $_len = 0;
                        }
                        $_plaintext = substr($_iv, $_orig_pos) ^ $_text;
                        $_iv = substr_replace($_iv, substr($_text, 0, $_i), $_orig_pos, $_i);
                    }
                    while ($_len >= '.$block_size.') {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_iv = $in;
                        $cb = substr($_text, $_i, '.$block_size.');
                        $_plaintext.= $_iv ^ $cb;
                        $_iv = $cb;
                        $_len-= '.$block_size.';
                        $_i+= '.$block_size.';
                    }
                    if ($_len) {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_iv = $in;
                        $_plaintext.= $_iv ^ substr($_text, $_i);
                        $_iv = substr_replace($_iv, substr($_text, $_i), 0, $_len);
                        $_pos = $_len;
                    }

                    return $_plaintext;
                    ';
                break;
            case self::MODE_CFB8:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_len = strlen($_text);
                    $_iv = $this->encryptIV;

                    for ($_i = 0; $_i < $_len; ++$_i) {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_ciphertext .= ($_c = $_text[$_i] ^ $in);
                        $_iv = substr($_iv, 1) . $_c;
                    }

                    if ($this->continuousBuffer) {
                        if ($_len >= '.$block_size.') {
                            $this->encryptIV = substr($_ciphertext, -'.$block_size.');
                        } else {
                            $this->encryptIV = substr($this->encryptIV, $_len - '.$block_size.') . substr($_ciphertext, -$_len);
                        }
                    }

                    return $_ciphertext;
                    ';
                $decrypt = $init_encrypt . '
                    $_plaintext = "";
                    $_len = strlen($_text);
                    $_iv = $this->decryptIV;

                    for ($_i = 0; $_i < $_len; ++$_i) {
                        $in = $_iv;
                        '.$encrypt_block.'
                        $_plaintext .= $_text[$_i] ^ $in;
                        $_iv = substr($_iv, 1) . $_text[$_i];
                    }

                    if ($this->continuousBuffer) {
                        if ($_len >= '.$block_size.') {
                            $this->decryptIV = substr($_text, -'.$block_size.');
                        } else {
                            $this->decryptIV = substr($this->decryptIV, $_len - '.$block_size.') . substr($_text, -$_len);
                        }
                    }

                    return $_plaintext;
                    ';
                break;
            case self::MODE_OFB:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_plaintext_len = strlen($_text);
                    $_xor = $this->encryptIV;
                    $_buffer = &$this->enbuffer;

                    if (strlen($_buffer["xor"])) {
                        for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            if (strlen($_block) > strlen($_buffer["xor"])) {
                                $in = $_xor;
                                '.$encrypt_block.'
                                $_xor = $in;
                                $_buffer["xor"].= $_xor;
                            }
                            $_key = \phpseclib3\Common\Functions\Strings::shift($_buffer["xor"], '.$block_size.');
                            $_ciphertext.= $_block ^ $_key;
                        }
                    } else {
                        for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                            $in = $_xor;
                            '.$encrypt_block.'
                            $_xor = $in;
                            $_ciphertext.= substr($_text, $_i, '.$block_size.') ^ $_xor;
                        }
                        $_key = $_xor;
                    }
                    if ($this->continuousBuffer) {
                        $this->encryptIV = $_xor;
                        if ($_start = $_plaintext_len % '.$block_size.') {
                             $_buffer["xor"] = substr($_key, $_start) . $_buffer["xor"];
                        }
                    }
                    return $_ciphertext;
                    ';

                $decrypt = $init_encrypt . '
                    $_plaintext = "";
                    $_ciphertext_len = strlen($_text);
                    $_xor = $this->decryptIV;
                    $_buffer = &$this->debuffer;

                    if (strlen($_buffer["xor"])) {
                        for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                            $_block = substr($_text, $_i, '.$block_size.');
                            if (strlen($_block) > strlen($_buffer["xor"])) {
                                $in = $_xor;
                                '.$encrypt_block.'
                                $_xor = $in;
                                $_buffer["xor"].= $_xor;
                            }
                            $_key = \phpseclib3\Common\Functions\Strings::shift($_buffer["xor"], '.$block_size.');
                            $_plaintext.= $_block ^ $_key;
                        }
                    } else {
                        for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                            $in = $_xor;
                            '.$encrypt_block.'
                            $_xor = $in;
                            $_plaintext.= substr($_text, $_i, '.$block_size.') ^ $_xor;
                        }
                        $_key = $_xor;
                    }
                    if ($this->continuousBuffer) {
                        $this->decryptIV = $_xor;
                        if ($_start = $_ciphertext_len % '.$block_size.') {
                             $_buffer["xor"] = substr($_key, $_start) . $_buffer["xor"];
                        }
                    }
                    return $_plaintext;
                    ';
                break;
            case self::MODE_STREAM:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    '.$encrypt_block.'
                    return $_ciphertext;
                    ';
                $decrypt = $init_decrypt . '
                    $_plaintext = "";
                    '.$decrypt_block.'
                    return $_plaintext;
                    ';
                break;
            // case self::MODE_CBC:
            default:
                $encrypt = $init_encrypt . '
                    $_ciphertext = "";
                    $_plaintext_len = strlen($_text);

                    $in = $this->encryptIV;

                    for ($_i = 0; $_i < $_plaintext_len; $_i+= '.$block_size.') {
                        $in = substr($_text, $_i, '.$block_size.') ^ $in;
                        '.$encrypt_block.'
                        $_ciphertext.= $in;
                    }

                    if ($this->continuousBuffer) {
                        $this->encryptIV = $in;
                    }

                    return $_ciphertext;
                    ';

                $decrypt = $init_decrypt . '
                    $_plaintext = "";
                    $_text = str_pad($_text, strlen($_text) + ('.$block_size.' - strlen($_text) % '.$block_size.') % '.$block_size.', chr(0));
                    $_ciphertext_len = strlen($_text);

                    $_iv = $this->decryptIV;

                    for ($_i = 0; $_i < $_ciphertext_len; $_i+= '.$block_size.') {
                        $in = $_block = substr($_text, $_i, '.$block_size.');
                        '.$decrypt_block.'
                        $_plaintext.= $in ^ $_iv;
                        $_iv = $_block;
                    }

                    if ($this->continuousBuffer) {
                        $this->decryptIV = $_iv;
                    }

                    return $this->unpad($_plaintext);
                    ';
                break;
        }

        // Before discrediting this, please read the following:
        // @see https://github.com/phpseclib/phpseclib/issues/1293
        // @see https://github.com/phpseclib/phpseclib/pull/1143
        eval('$func = function ($_action, $_text) { ' . $init_crypt . 'if ($_action == "encrypt") { ' . $encrypt . ' } else { ' . $decrypt . ' }};');

        return \Closure::bind($func, $this, static::class);
    }

    /**
     * Convert float to int
     *
     * On ARM CPUs converting floats to ints doesn't always work
     *
     * @access private
     * @param string $x
     * @return int
     */
    protected static function safe_intval($x)
    {
        switch (true) {
            case is_int($x):
            // PHP 5.3, per http://php.net/releases/5_3_0.php, introduced "more consistent float rounding"
            case (php_uname('m') & "\xDF\xDF\xDF") != 'ARM':
                return $x;
        }
        return (fmod($x, 0x80000000) & 0x7FFFFFFF) |
            ((fmod(floor($x / 0x80000000), 2) & 1) << 31);
    }

    /**
     * eval()'able string for in-line float to int
     *
     * @access private
     * @return string
     */
    protected static function safe_intval_inline()
    {
        switch (true) {
            case defined('PHP_INT_SIZE') && PHP_INT_SIZE == 8:
            case (php_uname('m') & "\xDF\xDF\xDF") != 'ARM':
                return '%s';
                break;
            default:
                $safeint = '(is_int($temp = %s) ? $temp : (fmod($temp, 0x80000000) & 0x7FFFFFFF) | ';
                return $safeint . '((fmod(floor($temp / 0x80000000), 2) & 1) << 31))';
        }
    }

    /**
     * Sets up GCM parameters
     *
     * See steps 1-2 of https://nvlpubs.nist.gov/nistpubs/Legacy/SP/nistspecialpublication800-38d.pdf#page=23
     * for more info
     *
     * @access private
     */
    private function setupGCM()
    {
        // don't keep on re-calculating $this->h
        if (!$this->h || $this->h->key != $this->key) {
            $cipher = new static('ecb');
            $cipher->setKey($this->key);
            $cipher->disablePadding();

            $this->h = self::$gcmField->newInteger(
                Strings::switchEndianness($cipher->encrypt("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"))
            );
            $this->h->key = $this->key;
        }

        if (strlen($this->nonce) == 12) {
            $this->iv = $this->nonce . "\0\0\0\1";
        } else {
            $this->iv = $this->ghash(
                self::nullPad128($this->nonce) . str_repeat("\0", 8) . self::len64($this->nonce)
            );
        }
    }

    /**
     * Performs GHASH operation
     *
     * See https://nvlpubs.nist.gov/nistpubs/Legacy/SP/nistspecialpublication800-38d.pdf#page=20
     * for more info
     *
     * @see self::decrypt()
     * @see self::encrypt()
     * @access private
     * @param string $x
     * @return string
     */
    private function ghash($x)
    {
        $h = $this->h;
        $y = ["\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"];
        $x = str_split($x, 16);
        $n = 0;
        // the switchEndianness calls are necessary because the multiplication algorithm in BinaryField/Integer
        // interprets strings as polynomials in big endian order whereas in GCM they're interpreted in little
        // endian order per https://nvlpubs.nist.gov/nistpubs/Legacy/SP/nistspecialpublication800-38d.pdf#page=19.
        // big endian order is what binary field elliptic curves use per http://www.secg.org/sec1-v2.pdf#page=18.

        // we could switchEndianness here instead of in the while loop but doing so in the while loop seems like it
        // might be slightly more performant
        //$x = Strings::switchEndianness($x);
        foreach ($x as $xn) {
            $xn = Strings::switchEndianness($xn);
            $t = $y[$n] ^ $xn;
            $temp = self::$gcmField->newInteger($t);
            $y[++$n] = $temp->multiply($h)->toBytes();
            $y[$n] = substr($y[$n], 1);
        }
        $y[$n] = Strings::switchEndianness($y[$n]);
        return $y[$n];
    }

    /**
     * Returns the bit length of a string in a packed format
     *
     * @see self::decrypt()
     * @see self::encrypt()
     * @see self::setupGCM()
     * @access private
     * @param string $str
     * @return string
     */
    private static function len64($str)
    {
        return "\0\0\0\0" . pack('N', 8 * strlen($str));
    }

    /**
     * NULL pads a string to be a multiple of 128
     *
     * @see self::decrypt()
     * @see self::encrypt()
     * @see self::setupGCM()
     * @access private
     * @param string $str
     * @return string
     */
    protected static function nullPad128($str)
    {
        $len = strlen($str);
        return $str . str_repeat("\0", 16 * ceil($len / 16) - $len);
    }

    /**
     * Calculates Poly1305 MAC
     *
     * On my system ChaCha20, with libsodium, takes 0.5s. With this custom Poly1305 implementation
     * it takes 1.2s.
     *
     * @see self::decrypt()
     * @see self::encrypt()
     * @access private
     * @param string $text
     * @return string
     */
    protected function poly1305($text)
    {
        $s = $this->poly1305Key; // strlen($this->poly1305Key) == 32
        $r = Strings::shift($s, 16);
        $r = strrev($r);
        $r&= "\x0f\xff\xff\xfc\x0f\xff\xff\xfc\x0f\xff\xff\xfc\x0f\xff\xff\xff";
        $s = strrev($s);

        $r = self::$poly1305Field->newInteger(new BigInteger($r, 256));
        $s = self::$poly1305Field->newInteger(new BigInteger($s, 256));
        $a = self::$poly1305Field->newInteger(new BigInteger());

        $blocks = str_split($text, 16);
        foreach ($blocks as $block) {
            $n = strrev($block . chr(1));
            $n = self::$poly1305Field->newInteger(new BigInteger($n, 256));
            $a = $a->add($n);
            $a = $a->multiply($r);
        }
        $r = $a->toBigInteger()->add($s->toBigInteger());
        $mask = "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF";
        return strrev($r->toBytes()) & $mask;
    }
}
 
abstract class BlockCipher extends SymmetricKey
{
}

class Rijndael extends BlockCipher
{
    /**
     * The mcrypt specific name of the cipher
     *
     * Mcrypt is useable for 128/192/256-bit $block_size/$key_length. For 160/224 not.
     * \phpseclib3\Crypt\Rijndael determines automatically whether mcrypt is useable
     * or not for the current $block_size/$key_length.
     * In case of, $cipher_name_mcrypt will be set dynamically at run time accordingly.
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::cipher_name_mcrypt
     * @see \phpseclib3\Crypt\Common\SymmetricKey::engine
     * @see self::isValidEngine()
     * @var string
     * @access private
     */
    protected $cipher_name_mcrypt = 'rijndael-128';

    /**
     * The Key Schedule
     *
     * @see self::setup()
     * @var array
     * @access private
     */
    private $w;

    /**
     * The Inverse Key Schedule
     *
     * @see self::setup()
     * @var array
     * @access private
     */
    private $dw;

    /**
     * The Block Length divided by 32
     *
     * {@internal The max value is 256 / 32 = 8, the min value is 128 / 32 = 4.  Exists in conjunction with $block_size
     *    because the encryption / decryption / key schedule creation requires this number and not $block_size.  We could
     *    derive this from $block_size or vice versa, but that'd mean we'd have to do multiple shift operations, so in lieu
     *    of that, we'll just precompute it once.}
     *
     * @see self::setBlockLength()
     * @var int
     * @access private
     */
    private $Nb = 4;

    /**
     * The Key Length (in bytes)
     *
     * {@internal The max value is 256 / 8 = 32, the min value is 128 / 8 = 16.  Exists in conjunction with $Nk
     *    because the encryption / decryption / key schedule creation requires this number and not $key_length.  We could
     *    derive this from $key_length or vice versa, but that'd mean we'd have to do multiple shift operations, so in lieu
     *    of that, we'll just precompute it once.}
     *
     * @see self::setKeyLength()
     * @var int
     * @access private
     */
    protected $key_length = 16;

    /**
     * The Key Length divided by 32
     *
     * @see self::setKeyLength()
     * @var int
     * @access private
     * @internal The max value is 256 / 32 = 8, the min value is 128 / 32 = 4
     */
    private $Nk = 4;

    /**
     * The Number of Rounds
     *
     * {@internal The max value is 14, the min value is 10.}
     *
     * @var int
     * @access private
     */
    private $Nr;

    /**
     * Shift offsets
     *
     * @var array
     * @access private
     */
    private $c;

    /**
     * Holds the last used key- and block_size information
     *
     * @var array
     * @access private
     */
    private $kl;

    /**
     * Default Constructor.
     *
     * @param string $mode
     * @access public
     * @throws \InvalidArgumentException if an invalid / unsupported mode is provided
     */
    public function __construct($mode)
    {
        parent::__construct($mode);

        if ($this->mode == self::MODE_STREAM) {
            throw new BadModeException('Block ciphers cannot be ran in stream mode');
        }
    }

    /**
     * Sets the key length.
     *
     * Valid key lengths are 128, 160, 192, 224, and 256.
     *
     * Note: phpseclib extends Rijndael (and AES) for using 160- and 224-bit keys but they are officially not defined
     *       and the most (if not all) implementations are not able using 160/224-bit keys but round/pad them up to
     *       192/256 bits as, for example, mcrypt will do.
     *
     *       That said, if you want be compatible with other Rijndael and AES implementations,
     *       you should not setKeyLength(160) or setKeyLength(224).
     *
     * Additional: In case of 160- and 224-bit keys, phpseclib will/can, for that reason, not use
     *             the mcrypt php extension, even if available.
     *             This results then in slower encryption.
     *
     * @access public
     * @throws \LengthException if the key length is invalid
     * @param int $length
     */
    public function setKeyLength($length)
    {
        switch ($length) {
            case 128:
            case 160:
            case 192:
            case 224:
            case 256:
                $this->key_length = $length >> 3;
                break;
            default:
                throw new \LengthException('Key size of ' . $length . ' bits is not supported by this algorithm. Only keys of sizes 128, 160, 192, 224 or 256 bits are supported');
        }

        parent::setKeyLength($length);
    }

    /**
     * Sets the key.
     *
     * Rijndael supports five different key lengths
     *
     * @see setKeyLength()
     * @access public
     * @param string $key
     * @throws \LengthException if the key length isn't supported
     */
    public function setKey($key)
    {
        switch (strlen($key)) {
            case 16:
            case 20:
            case 24:
            case 28:
            case 32:
                break;
            default:
                throw new \LengthException('Key of size ' . strlen($key) . ' not supported by this algorithm. Only keys of sizes 16, 20, 24, 28 or 32 are supported');
        }

        parent::setKey($key);
    }

    /**
     * Sets the block length
     *
     * Valid block lengths are 128, 160, 192, 224, and 256.
     *
     * @access public
     * @param int $length
     */
    public function setBlockLength($length)
    {
        switch ($length) {
            case 128:
            case 160:
            case 192:
            case 224:
            case 256:
                break;
            default:
                throw new \LengthException('Key size of ' . $length . ' bits is not supported by this algorithm. Only keys of sizes 128, 160, 192, 224 or 256 bits are supported');
        }

        $this->Nb = $length >> 5;
        $this->block_size = $length >> 3;
        $this->changed = $this->nonIVChanged = true;
        $this->setEngine();
    }

    /**
     * Test for engine validity
     *
     * This is mainly just a wrapper to set things up for \phpseclib3\Crypt\Common\SymmetricKey::isValidEngine()
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     * @param int $engine
     * @access protected
     * @return bool
     */
    protected function isValidEngineHelper($engine)
    {
        switch ($engine) {
            case self::ENGINE_LIBSODIUM:
                return function_exists('sodium_crypto_aead_aes256gcm_is_available') &&
                       sodium_crypto_aead_aes256gcm_is_available() &&
                       $this->mode == self::MODE_GCM &&
                       $this->key_length == 32 &&
                       $this->nonce && strlen($this->nonce) == 12 &&
                       $this->block_size == 16;
            case self::ENGINE_OPENSSL_GCM:
                if (!extension_loaded('openssl')) {
                    return false;
                }
                $methods = openssl_get_cipher_methods();
                return $this->mode == self::MODE_GCM &&
                       version_compare(PHP_VERSION, '7.1.0', '>=') &&
                       in_array('aes-' . $this->getKeyLength() . '-gcm', $methods) &&
                       $this->block_size == 16;
            case self::ENGINE_OPENSSL:
                if ($this->block_size != 16) {
                    return false;
                }
                self::$cipher_name_openssl_ecb = 'aes-' . ($this->key_length << 3) . '-ecb';
                $this->cipher_name_openssl = 'aes-' . ($this->key_length << 3) . '-' . $this->openssl_translate_mode();
                break;
            case self::ENGINE_MCRYPT:
                $this->cipher_name_mcrypt = 'rijndael-' . ($this->block_size << 3);
                if ($this->key_length % 8) { // is it a 160/224-bit key?
                    // mcrypt is not usable for them, only for 128/192/256-bit keys
                    return false;
                }
        }

        return parent::isValidEngineHelper($engine);
    }

    /**
     * Encrypts a block
     *
     * @access private
     * @param string $in
     * @return string
     */
    protected function encryptBlock($in)
    {
        static $tables;
        if (empty($tables)) {
            $tables = &$this->getTables();
        }
        $t0   = $tables[0];
        $t1   = $tables[1];
        $t2   = $tables[2];
        $t3   = $tables[3];
        $sbox = $tables[4];

        $state = [];
        $words = unpack('N*', $in);

        $c = $this->c;
        $w = $this->w;
        $Nb = $this->Nb;
        $Nr = $this->Nr;

        // addRoundKey
        $wc = $Nb - 1;
        foreach ($words as $word) {
            $state[] = $word ^ $w[++$wc];
        }

        // fips-197.pdf#page=19, "Figure 5. Pseudo Code for the Cipher", states that this loop has four components -
        // subBytes, shiftRows, mixColumns, and addRoundKey. fips-197.pdf#page=30, "Implementation Suggestions Regarding
        // Various Platforms" suggests that performs enhanced implementations are described in Rijndael-ammended.pdf.
        // Rijndael-ammended.pdf#page=20, "Implementation aspects / 32-bit processor", discusses such an optimization.
        // Unfortunately, the description given there is not quite correct.  Per aes.spec.v316.pdf#page=19 [1],
        // equation (7.4.7) is supposed to use addition instead of subtraction, so we'll do that here, as well.

        // [1] http://fp.gladman.plus.com/cryptography_technology/rijndael/aes.spec.v316.pdf
        $temp = [];
        for ($round = 1; $round < $Nr; ++$round) {
            $i = 0; // $c[0] == 0
            $j = $c[1];
            $k = $c[2];
            $l = $c[3];

            while ($i < $Nb) {
                $temp[$i] = $t0[$state[$i] >> 24 & 0x000000FF] ^
                            $t1[$state[$j] >> 16 & 0x000000FF] ^
                            $t2[$state[$k] >>  8 & 0x000000FF] ^
                            $t3[$state[$l]       & 0x000000FF] ^
                            $w[++$wc];
                ++$i;
                $j = ($j + 1) % $Nb;
                $k = ($k + 1) % $Nb;
                $l = ($l + 1) % $Nb;
            }
            $state = $temp;
        }

        // subWord
        for ($i = 0; $i < $Nb; ++$i) {
            $state[$i] =   $sbox[$state[$i]       & 0x000000FF]        |
                          ($sbox[$state[$i] >>  8 & 0x000000FF] <<  8) |
                          ($sbox[$state[$i] >> 16 & 0x000000FF] << 16) |
                          ($sbox[$state[$i] >> 24 & 0x000000FF] << 24);
        }

        // shiftRows + addRoundKey
        $i = 0; // $c[0] == 0
        $j = $c[1];
        $k = $c[2];
        $l = $c[3];
        while ($i < $Nb) {
            $temp[$i] = ($state[$i] & 0xFF000000) ^
                        ($state[$j] & 0x00FF0000) ^
                        ($state[$k] & 0x0000FF00) ^
                        ($state[$l] & 0x000000FF) ^
                         $w[$i];
            ++$i;
            $j = ($j + 1) % $Nb;
            $k = ($k + 1) % $Nb;
            $l = ($l + 1) % $Nb;
        }

        return pack('N*', ...$temp);
    }

    /**
     * Decrypts a block
     *
     * @access private
     * @param string $in
     * @return string
     */
    protected function decryptBlock($in)
    {
        static $invtables;
        if (empty($invtables)) {
            $invtables = &$this->getInvTables();
        }
        $dt0   = $invtables[0];
        $dt1   = $invtables[1];
        $dt2   = $invtables[2];
        $dt3   = $invtables[3];
        $isbox = $invtables[4];

        $state = [];
        $words = unpack('N*', $in);

        $c  = $this->c;
        $dw = $this->dw;
        $Nb = $this->Nb;
        $Nr = $this->Nr;

        // addRoundKey
        $wc = $Nb - 1;
        foreach ($words as $word) {
            $state[] = $word ^ $dw[++$wc];
        }

        $temp = [];
        for ($round = $Nr - 1; $round > 0; --$round) {
            $i = 0; // $c[0] == 0
            $j = $Nb - $c[1];
            $k = $Nb - $c[2];
            $l = $Nb - $c[3];

            while ($i < $Nb) {
                $temp[$i] = $dt0[$state[$i] >> 24 & 0x000000FF] ^
                            $dt1[$state[$j] >> 16 & 0x000000FF] ^
                            $dt2[$state[$k] >>  8 & 0x000000FF] ^
                            $dt3[$state[$l]       & 0x000000FF] ^
                            $dw[++$wc];
                ++$i;
                $j = ($j + 1) % $Nb;
                $k = ($k + 1) % $Nb;
                $l = ($l + 1) % $Nb;
            }
            $state = $temp;
        }

        // invShiftRows + invSubWord + addRoundKey
        $i = 0; // $c[0] == 0
        $j = $Nb - $c[1];
        $k = $Nb - $c[2];
        $l = $Nb - $c[3];

        while ($i < $Nb) {
            $word = ($state[$i] & 0xFF000000) |
                    ($state[$j] & 0x00FF0000) |
                    ($state[$k] & 0x0000FF00) |
                    ($state[$l] & 0x000000FF);

            $temp[$i] = $dw[$i] ^ ($isbox[$word       & 0x000000FF]        |
                                  ($isbox[$word >>  8 & 0x000000FF] <<  8) |
                                  ($isbox[$word >> 16 & 0x000000FF] << 16) |
                                  ($isbox[$word >> 24 & 0x000000FF] << 24));
            ++$i;
            $j = ($j + 1) % $Nb;
            $k = ($k + 1) % $Nb;
            $l = ($l + 1) % $Nb;
        }

        return pack('N*', ...$temp);
    }

    /**
     * Setup the key (expansion)
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::setupKey()
     * @access private
     */
    protected function setupKey()
    {
        // Each number in $rcon is equal to the previous number multiplied by two in Rijndael's finite field.
        // See http://en.wikipedia.org/wiki/Finite_field_arithmetic#Multiplicative_inverse
        static $rcon = [0,
            0x01000000, 0x02000000, 0x04000000, 0x08000000, 0x10000000,
            0x20000000, 0x40000000, 0x80000000, 0x1B000000, 0x36000000,
            0x6C000000, 0xD8000000, 0xAB000000, 0x4D000000, 0x9A000000,
            0x2F000000, 0x5E000000, 0xBC000000, 0x63000000, 0xC6000000,
            0x97000000, 0x35000000, 0x6A000000, 0xD4000000, 0xB3000000,
            0x7D000000, 0xFA000000, 0xEF000000, 0xC5000000, 0x91000000
        ];

        if (isset($this->kl['key']) && $this->key === $this->kl['key'] && $this->key_length === $this->kl['key_length'] && $this->block_size === $this->kl['block_size']) {
            // already expanded
            return;
        }
        $this->kl = ['key' => $this->key, 'key_length' => $this->key_length, 'block_size' => $this->block_size];

        $this->Nk = $this->key_length >> 2;
        // see Rijndael-ammended.pdf#page=44
        $this->Nr = max($this->Nk, $this->Nb) + 6;

        // shift offsets for Nb = 5, 7 are defined in Rijndael-ammended.pdf#page=44,
        //     "Table 8: Shift offsets in Shiftrow for the alternative block lengths"
        // shift offsets for Nb = 4, 6, 8 are defined in Rijndael-ammended.pdf#page=14,
        //     "Table 2: Shift offsets for different block lengths"
        switch ($this->Nb) {
            case 4:
            case 5:
            case 6:
                $this->c = [0, 1, 2, 3];
                break;
            case 7:
                $this->c = [0, 1, 2, 4];
                break;
            case 8:
                $this->c = [0, 1, 3, 4];
        }

        $w = array_values(unpack('N*words', $this->key));

        $length = $this->Nb * ($this->Nr + 1);
        for ($i = $this->Nk; $i < $length; $i++) {
            $temp = $w[$i - 1];
            if ($i % $this->Nk == 0) {
                // according to <http://php.net/language.types.integer>, "the size of an integer is platform-dependent".
                // on a 32-bit machine, it's 32-bits, and on a 64-bit machine, it's 64-bits. on a 32-bit machine,
                // 0xFFFFFFFF << 8 == 0xFFFFFF00, but on a 64-bit machine, it equals 0xFFFFFFFF00. as such, doing 'and'
                // with 0xFFFFFFFF (or 0xFFFFFF00) on a 32-bit machine is unnecessary, but on a 64-bit machine, it is.
                $temp = (($temp << 8) & 0xFFFFFF00) | (($temp >> 24) & 0x000000FF); // rotWord
                $temp = $this->subWord($temp) ^ $rcon[$i / $this->Nk];
            } elseif ($this->Nk > 6 && $i % $this->Nk == 4) {
                $temp = $this->subWord($temp);
            }
            $w[$i] = $w[$i - $this->Nk] ^ $temp;
        }

        // convert the key schedule from a vector of $Nb * ($Nr + 1) length to a matrix with $Nr + 1 rows and $Nb columns
        // and generate the inverse key schedule.  more specifically,
        // according to <http://csrc.nist.gov/archive/aes/rijndael/Rijndael-ammended.pdf#page=23> (section 5.3.3),
        // "The key expansion for the Inverse Cipher is defined as follows:
        //        1. Apply the Key Expansion.
        //        2. Apply InvMixColumn to all Round Keys except the first and the last one."
        // also, see fips-197.pdf#page=27, "5.3.5 Equivalent Inverse Cipher"
        list($dt0, $dt1, $dt2, $dt3) = $this->getInvTables();
        $temp = $this->w = $this->dw = [];
        for ($i = $row = $col = 0; $i < $length; $i++, $col++) {
            if ($col == $this->Nb) {
                if ($row == 0) {
                    $this->dw[0] = $this->w[0];
                } else {
                    // subWord + invMixColumn + invSubWord = invMixColumn
                    $j = 0;
                    while ($j < $this->Nb) {
                        $dw = $this->subWord($this->w[$row][$j]);
                        $temp[$j] = $dt0[$dw >> 24 & 0x000000FF] ^
                                    $dt1[$dw >> 16 & 0x000000FF] ^
                                    $dt2[$dw >>  8 & 0x000000FF] ^
                                    $dt3[$dw       & 0x000000FF];
                        $j++;
                    }
                    $this->dw[$row] = $temp;
                }

                $col = 0;
                $row++;
            }
            $this->w[$row][$col] = $w[$i];
        }

        $this->dw[$row] = $this->w[$row];

        // Converting to 1-dim key arrays (both ascending)
        $this->dw = array_reverse($this->dw);
        $w  = array_pop($this->w);
        $dw = array_pop($this->dw);
        foreach ($this->w as $r => $wr) {
            foreach ($wr as $c => $wc) {
                $w[]  = $wc;
                $dw[] = $this->dw[$r][$c];
            }
        }
        $this->w  = $w;
        $this->dw = $dw;
    }

    /**
     * Performs S-Box substitutions
     *
     * @return array
     * @access private
     * @param int $word
     */
    private function subWord($word)
    {
        static $sbox;
        if (empty($sbox)) {
            list(, , , , $sbox) = self::getTables();
        }

        return  $sbox[$word       & 0x000000FF]        |
               ($sbox[$word >>  8 & 0x000000FF] <<  8) |
               ($sbox[$word >> 16 & 0x000000FF] << 16) |
               ($sbox[$word >> 24 & 0x000000FF] << 24);
    }

    /**
     * Provides the mixColumns and sboxes tables
     *
     * @see self::encryptBlock()
     * @see self::setupInlineCrypt()
     * @see self::subWord()
     * @access private
     * @return array &$tables
     */
    protected function &getTables()
    {
        static $tables;
        if (empty($tables)) {
            // according to <http://csrc.nist.gov/archive/aes/rijndael/Rijndael-ammended.pdf#page=19> (section 5.2.1),
            // precomputed tables can be used in the mixColumns phase. in that example, they're assigned t0...t3, so
            // those are the names we'll use.
            $t3 = array_map('intval', [
                // with array_map('intval', ...) we ensure we have only int's and not
                // some slower floats converted by php automatically on high values
                0x6363A5C6, 0x7C7C84F8, 0x777799EE, 0x7B7B8DF6, 0xF2F20DFF, 0x6B6BBDD6, 0x6F6FB1DE, 0xC5C55491,
                0x30305060, 0x01010302, 0x6767A9CE, 0x2B2B7D56, 0xFEFE19E7, 0xD7D762B5, 0xABABE64D, 0x76769AEC,
                0xCACA458F, 0x82829D1F, 0xC9C94089, 0x7D7D87FA, 0xFAFA15EF, 0x5959EBB2, 0x4747C98E, 0xF0F00BFB,
                0xADADEC41, 0xD4D467B3, 0xA2A2FD5F, 0xAFAFEA45, 0x9C9CBF23, 0xA4A4F753, 0x727296E4, 0xC0C05B9B,
                0xB7B7C275, 0xFDFD1CE1, 0x9393AE3D, 0x26266A4C, 0x36365A6C, 0x3F3F417E, 0xF7F702F5, 0xCCCC4F83,
                0x34345C68, 0xA5A5F451, 0xE5E534D1, 0xF1F108F9, 0x717193E2, 0xD8D873AB, 0x31315362, 0x15153F2A,
                0x04040C08, 0xC7C75295, 0x23236546, 0xC3C35E9D, 0x18182830, 0x9696A137, 0x05050F0A, 0x9A9AB52F,
                0x0707090E, 0x12123624, 0x80809B1B, 0xE2E23DDF, 0xEBEB26CD, 0x2727694E, 0xB2B2CD7F, 0x75759FEA,
                0x09091B12, 0x83839E1D, 0x2C2C7458, 0x1A1A2E34, 0x1B1B2D36, 0x6E6EB2DC, 0x5A5AEEB4, 0xA0A0FB5B,
                0x5252F6A4, 0x3B3B4D76, 0xD6D661B7, 0xB3B3CE7D, 0x29297B52, 0xE3E33EDD, 0x2F2F715E, 0x84849713,
                0x5353F5A6, 0xD1D168B9, 0x00000000, 0xEDED2CC1, 0x20206040, 0xFCFC1FE3, 0xB1B1C879, 0x5B5BEDB6,
                0x6A6ABED4, 0xCBCB468D, 0xBEBED967, 0x39394B72, 0x4A4ADE94, 0x4C4CD498, 0x5858E8B0, 0xCFCF4A85,
                0xD0D06BBB, 0xEFEF2AC5, 0xAAAAE54F, 0xFBFB16ED, 0x4343C586, 0x4D4DD79A, 0x33335566, 0x85859411,
                0x4545CF8A, 0xF9F910E9, 0x02020604, 0x7F7F81FE, 0x5050F0A0, 0x3C3C4478, 0x9F9FBA25, 0xA8A8E34B,
                0x5151F3A2, 0xA3A3FE5D, 0x4040C080, 0x8F8F8A05, 0x9292AD3F, 0x9D9DBC21, 0x38384870, 0xF5F504F1,
                0xBCBCDF63, 0xB6B6C177, 0xDADA75AF, 0x21216342, 0x10103020, 0xFFFF1AE5, 0xF3F30EFD, 0xD2D26DBF,
                0xCDCD4C81, 0x0C0C1418, 0x13133526, 0xECEC2FC3, 0x5F5FE1BE, 0x9797A235, 0x4444CC88, 0x1717392E,
                0xC4C45793, 0xA7A7F255, 0x7E7E82FC, 0x3D3D477A, 0x6464ACC8, 0x5D5DE7BA, 0x19192B32, 0x737395E6,
                0x6060A0C0, 0x81819819, 0x4F4FD19E, 0xDCDC7FA3, 0x22226644, 0x2A2A7E54, 0x9090AB3B, 0x8888830B,
                0x4646CA8C, 0xEEEE29C7, 0xB8B8D36B, 0x14143C28, 0xDEDE79A7, 0x5E5EE2BC, 0x0B0B1D16, 0xDBDB76AD,
                0xE0E03BDB, 0x32325664, 0x3A3A4E74, 0x0A0A1E14, 0x4949DB92, 0x06060A0C, 0x24246C48, 0x5C5CE4B8,
                0xC2C25D9F, 0xD3D36EBD, 0xACACEF43, 0x6262A6C4, 0x9191A839, 0x9595A431, 0xE4E437D3, 0x79798BF2,
                0xE7E732D5, 0xC8C8438B, 0x3737596E, 0x6D6DB7DA, 0x8D8D8C01, 0xD5D564B1, 0x4E4ED29C, 0xA9A9E049,
                0x6C6CB4D8, 0x5656FAAC, 0xF4F407F3, 0xEAEA25CF, 0x6565AFCA, 0x7A7A8EF4, 0xAEAEE947, 0x08081810,
                0xBABAD56F, 0x787888F0, 0x25256F4A, 0x2E2E725C, 0x1C1C2438, 0xA6A6F157, 0xB4B4C773, 0xC6C65197,
                0xE8E823CB, 0xDDDD7CA1, 0x74749CE8, 0x1F1F213E, 0x4B4BDD96, 0xBDBDDC61, 0x8B8B860D, 0x8A8A850F,
                0x707090E0, 0x3E3E427C, 0xB5B5C471, 0x6666AACC, 0x4848D890, 0x03030506, 0xF6F601F7, 0x0E0E121C,
                0x6161A3C2, 0x35355F6A, 0x5757F9AE, 0xB9B9D069, 0x86869117, 0xC1C15899, 0x1D1D273A, 0x9E9EB927,
                0xE1E138D9, 0xF8F813EB, 0x9898B32B, 0x11113322, 0x6969BBD2, 0xD9D970A9, 0x8E8E8907, 0x9494A733,
                0x9B9BB62D, 0x1E1E223C, 0x87879215, 0xE9E920C9, 0xCECE4987, 0x5555FFAA, 0x28287850, 0xDFDF7AA5,
                0x8C8C8F03, 0xA1A1F859, 0x89898009, 0x0D0D171A, 0xBFBFDA65, 0xE6E631D7, 0x4242C684, 0x6868B8D0,
                0x4141C382, 0x9999B029, 0x2D2D775A, 0x0F0F111E, 0xB0B0CB7B, 0x5454FCA8, 0xBBBBD66D, 0x16163A2C
            ]);

            foreach ($t3 as $t3i) {
                $t0[] = (($t3i << 24) & 0xFF000000) | (($t3i >>  8) & 0x00FFFFFF);
                $t1[] = (($t3i << 16) & 0xFFFF0000) | (($t3i >> 16) & 0x0000FFFF);
                $t2[] = (($t3i <<  8) & 0xFFFFFF00) | (($t3i >> 24) & 0x000000FF);
            }

            $tables = [
                // The Precomputed mixColumns tables t0 - t3
                $t0,
                $t1,
                $t2,
                $t3,
                // The SubByte S-Box
                [
                    0x63, 0x7C, 0x77, 0x7B, 0xF2, 0x6B, 0x6F, 0xC5, 0x30, 0x01, 0x67, 0x2B, 0xFE, 0xD7, 0xAB, 0x76,
                    0xCA, 0x82, 0xC9, 0x7D, 0xFA, 0x59, 0x47, 0xF0, 0xAD, 0xD4, 0xA2, 0xAF, 0x9C, 0xA4, 0x72, 0xC0,
                    0xB7, 0xFD, 0x93, 0x26, 0x36, 0x3F, 0xF7, 0xCC, 0x34, 0xA5, 0xE5, 0xF1, 0x71, 0xD8, 0x31, 0x15,
                    0x04, 0xC7, 0x23, 0xC3, 0x18, 0x96, 0x05, 0x9A, 0x07, 0x12, 0x80, 0xE2, 0xEB, 0x27, 0xB2, 0x75,
                    0x09, 0x83, 0x2C, 0x1A, 0x1B, 0x6E, 0x5A, 0xA0, 0x52, 0x3B, 0xD6, 0xB3, 0x29, 0xE3, 0x2F, 0x84,
                    0x53, 0xD1, 0x00, 0xED, 0x20, 0xFC, 0xB1, 0x5B, 0x6A, 0xCB, 0xBE, 0x39, 0x4A, 0x4C, 0x58, 0xCF,
                    0xD0, 0xEF, 0xAA, 0xFB, 0x43, 0x4D, 0x33, 0x85, 0x45, 0xF9, 0x02, 0x7F, 0x50, 0x3C, 0x9F, 0xA8,
                    0x51, 0xA3, 0x40, 0x8F, 0x92, 0x9D, 0x38, 0xF5, 0xBC, 0xB6, 0xDA, 0x21, 0x10, 0xFF, 0xF3, 0xD2,
                    0xCD, 0x0C, 0x13, 0xEC, 0x5F, 0x97, 0x44, 0x17, 0xC4, 0xA7, 0x7E, 0x3D, 0x64, 0x5D, 0x19, 0x73,
                    0x60, 0x81, 0x4F, 0xDC, 0x22, 0x2A, 0x90, 0x88, 0x46, 0xEE, 0xB8, 0x14, 0xDE, 0x5E, 0x0B, 0xDB,
                    0xE0, 0x32, 0x3A, 0x0A, 0x49, 0x06, 0x24, 0x5C, 0xC2, 0xD3, 0xAC, 0x62, 0x91, 0x95, 0xE4, 0x79,
                    0xE7, 0xC8, 0x37, 0x6D, 0x8D, 0xD5, 0x4E, 0xA9, 0x6C, 0x56, 0xF4, 0xEA, 0x65, 0x7A, 0xAE, 0x08,
                    0xBA, 0x78, 0x25, 0x2E, 0x1C, 0xA6, 0xB4, 0xC6, 0xE8, 0xDD, 0x74, 0x1F, 0x4B, 0xBD, 0x8B, 0x8A,
                    0x70, 0x3E, 0xB5, 0x66, 0x48, 0x03, 0xF6, 0x0E, 0x61, 0x35, 0x57, 0xB9, 0x86, 0xC1, 0x1D, 0x9E,
                    0xE1, 0xF8, 0x98, 0x11, 0x69, 0xD9, 0x8E, 0x94, 0x9B, 0x1E, 0x87, 0xE9, 0xCE, 0x55, 0x28, 0xDF,
                    0x8C, 0xA1, 0x89, 0x0D, 0xBF, 0xE6, 0x42, 0x68, 0x41, 0x99, 0x2D, 0x0F, 0xB0, 0x54, 0xBB, 0x16
                ]
            ];
        }
        return $tables;
    }

    /**
     * Provides the inverse mixColumns and inverse sboxes tables
     *
     * @see self::decryptBlock()
     * @see self::setupInlineCrypt()
     * @see self::setupKey()
     * @access private
     * @return array &$tables
     */
    protected function &getInvTables()
    {
        static $tables;
        if (empty($tables)) {
            $dt3 = array_map('intval', [
                0xF4A75051, 0x4165537E, 0x17A4C31A, 0x275E963A, 0xAB6BCB3B, 0x9D45F11F, 0xFA58ABAC, 0xE303934B,
                0x30FA5520, 0x766DF6AD, 0xCC769188, 0x024C25F5, 0xE5D7FC4F, 0x2ACBD7C5, 0x35448026, 0x62A38FB5,
                0xB15A49DE, 0xBA1B6725, 0xEA0E9845, 0xFEC0E15D, 0x2F7502C3, 0x4CF01281, 0x4697A38D, 0xD3F9C66B,
                0x8F5FE703, 0x929C9515, 0x6D7AEBBF, 0x5259DA95, 0xBE832DD4, 0x7421D358, 0xE0692949, 0xC9C8448E,
                0xC2896A75, 0x8E7978F4, 0x583E6B99, 0xB971DD27, 0xE14FB6BE, 0x88AD17F0, 0x20AC66C9, 0xCE3AB47D,
                0xDF4A1863, 0x1A3182E5, 0x51336097, 0x537F4562, 0x6477E0B1, 0x6BAE84BB, 0x81A01CFE, 0x082B94F9,
                0x48685870, 0x45FD198F, 0xDE6C8794, 0x7BF8B752, 0x73D323AB, 0x4B02E272, 0x1F8F57E3, 0x55AB2A66,
                0xEB2807B2, 0xB5C2032F, 0xC57B9A86, 0x3708A5D3, 0x2887F230, 0xBFA5B223, 0x036ABA02, 0x16825CED,
                0xCF1C2B8A, 0x79B492A7, 0x07F2F0F3, 0x69E2A14E, 0xDAF4CD65, 0x05BED506, 0x34621FD1, 0xA6FE8AC4,
                0x2E539D34, 0xF355A0A2, 0x8AE13205, 0xF6EB75A4, 0x83EC390B, 0x60EFAA40, 0x719F065E, 0x6E1051BD,
                0x218AF93E, 0xDD063D96, 0x3E05AEDD, 0xE6BD464D, 0x548DB591, 0xC45D0571, 0x06D46F04, 0x5015FF60,
                0x98FB2419, 0xBDE997D6, 0x4043CC89, 0xD99E7767, 0xE842BDB0, 0x898B8807, 0x195B38E7, 0xC8EEDB79,
                0x7C0A47A1, 0x420FE97C, 0x841EC9F8, 0x00000000, 0x80868309, 0x2BED4832, 0x1170AC1E, 0x5A724E6C,
                0x0EFFFBFD, 0x8538560F, 0xAED51E3D, 0x2D392736, 0x0FD9640A, 0x5CA62168, 0x5B54D19B, 0x362E3A24,
                0x0A67B10C, 0x57E70F93, 0xEE96D2B4, 0x9B919E1B, 0xC0C54F80, 0xDC20A261, 0x774B695A, 0x121A161C,
                0x93BA0AE2, 0xA02AE5C0, 0x22E0433C, 0x1B171D12, 0x090D0B0E, 0x8BC7ADF2, 0xB6A8B92D, 0x1EA9C814,
                0xF1198557, 0x75074CAF, 0x99DDBBEE, 0x7F60FDA3, 0x01269FF7, 0x72F5BC5C, 0x663BC544, 0xFB7E345B,
                0x4329768B, 0x23C6DCCB, 0xEDFC68B6, 0xE4F163B8, 0x31DCCAD7, 0x63851042, 0x97224013, 0xC6112084,
                0x4A247D85, 0xBB3DF8D2, 0xF93211AE, 0x29A16DC7, 0x9E2F4B1D, 0xB230F3DC, 0x8652EC0D, 0xC1E3D077,
                0xB3166C2B, 0x70B999A9, 0x9448FA11, 0xE9642247, 0xFC8CC4A8, 0xF03F1AA0, 0x7D2CD856, 0x3390EF22,
                0x494EC787, 0x38D1C1D9, 0xCAA2FE8C, 0xD40B3698, 0xF581CFA6, 0x7ADE28A5, 0xB78E26DA, 0xADBFA43F,
                0x3A9DE42C, 0x78920D50, 0x5FCC9B6A, 0x7E466254, 0x8D13C2F6, 0xD8B8E890, 0x39F75E2E, 0xC3AFF582,
                0x5D80BE9F, 0xD0937C69, 0xD52DA96F, 0x2512B3CF, 0xAC993BC8, 0x187DA710, 0x9C636EE8, 0x3BBB7BDB,
                0x267809CD, 0x5918F46E, 0x9AB701EC, 0x4F9AA883, 0x956E65E6, 0xFFE67EAA, 0xBCCF0821, 0x15E8E6EF,
                0xE79BD9BA, 0x6F36CE4A, 0x9F09D4EA, 0xB07CD629, 0xA4B2AF31, 0x3F23312A, 0xA59430C6, 0xA266C035,
                0x4EBC3774, 0x82CAA6FC, 0x90D0B0E0, 0xA7D81533, 0x04984AF1, 0xECDAF741, 0xCD500E7F, 0x91F62F17,
                0x4DD68D76, 0xEFB04D43, 0xAA4D54CC, 0x9604DFE4, 0xD1B5E39E, 0x6A881B4C, 0x2C1FB8C1, 0x65517F46,
                0x5EEA049D, 0x8C355D01, 0x877473FA, 0x0B412EFB, 0x671D5AB3, 0xDBD25292, 0x105633E9, 0xD647136D,
                0xD7618C9A, 0xA10C7A37, 0xF8148E59, 0x133C89EB, 0xA927EECE, 0x61C935B7, 0x1CE5EDE1, 0x47B13C7A,
                0xD2DF599C, 0xF2733F55, 0x14CE7918, 0xC737BF73, 0xF7CDEA53, 0xFDAA5B5F, 0x3D6F14DF, 0x44DB8678,
                0xAFF381CA, 0x68C43EB9, 0x24342C38, 0xA3405FC2, 0x1DC37216, 0xE2250CBC, 0x3C498B28, 0x0D9541FF,
                0xA8017139, 0x0CB3DE08, 0xB4E49CD8, 0x56C19064, 0xCB84617B, 0x32B670D5, 0x6C5C7448, 0xB85742D0
            ]);

            foreach ($dt3 as $dt3i) {
                $dt0[] = (($dt3i << 24) & 0xFF000000) | (($dt3i >>  8) & 0x00FFFFFF);
                $dt1[] = (($dt3i << 16) & 0xFFFF0000) | (($dt3i >> 16) & 0x0000FFFF);
                $dt2[] = (($dt3i <<  8) & 0xFFFFFF00) | (($dt3i >> 24) & 0x000000FF);
            };

            $tables = [
                // The Precomputed inverse mixColumns tables dt0 - dt3
                $dt0,
                $dt1,
                $dt2,
                $dt3,
                // The inverse SubByte S-Box
                [
                    0x52, 0x09, 0x6A, 0xD5, 0x30, 0x36, 0xA5, 0x38, 0xBF, 0x40, 0xA3, 0x9E, 0x81, 0xF3, 0xD7, 0xFB,
                    0x7C, 0xE3, 0x39, 0x82, 0x9B, 0x2F, 0xFF, 0x87, 0x34, 0x8E, 0x43, 0x44, 0xC4, 0xDE, 0xE9, 0xCB,
                    0x54, 0x7B, 0x94, 0x32, 0xA6, 0xC2, 0x23, 0x3D, 0xEE, 0x4C, 0x95, 0x0B, 0x42, 0xFA, 0xC3, 0x4E,
                    0x08, 0x2E, 0xA1, 0x66, 0x28, 0xD9, 0x24, 0xB2, 0x76, 0x5B, 0xA2, 0x49, 0x6D, 0x8B, 0xD1, 0x25,
                    0x72, 0xF8, 0xF6, 0x64, 0x86, 0x68, 0x98, 0x16, 0xD4, 0xA4, 0x5C, 0xCC, 0x5D, 0x65, 0xB6, 0x92,
                    0x6C, 0x70, 0x48, 0x50, 0xFD, 0xED, 0xB9, 0xDA, 0x5E, 0x15, 0x46, 0x57, 0xA7, 0x8D, 0x9D, 0x84,
                    0x90, 0xD8, 0xAB, 0x00, 0x8C, 0xBC, 0xD3, 0x0A, 0xF7, 0xE4, 0x58, 0x05, 0xB8, 0xB3, 0x45, 0x06,
                    0xD0, 0x2C, 0x1E, 0x8F, 0xCA, 0x3F, 0x0F, 0x02, 0xC1, 0xAF, 0xBD, 0x03, 0x01, 0x13, 0x8A, 0x6B,
                    0x3A, 0x91, 0x11, 0x41, 0x4F, 0x67, 0xDC, 0xEA, 0x97, 0xF2, 0xCF, 0xCE, 0xF0, 0xB4, 0xE6, 0x73,
                    0x96, 0xAC, 0x74, 0x22, 0xE7, 0xAD, 0x35, 0x85, 0xE2, 0xF9, 0x37, 0xE8, 0x1C, 0x75, 0xDF, 0x6E,
                    0x47, 0xF1, 0x1A, 0x71, 0x1D, 0x29, 0xC5, 0x89, 0x6F, 0xB7, 0x62, 0x0E, 0xAA, 0x18, 0xBE, 0x1B,
                    0xFC, 0x56, 0x3E, 0x4B, 0xC6, 0xD2, 0x79, 0x20, 0x9A, 0xDB, 0xC0, 0xFE, 0x78, 0xCD, 0x5A, 0xF4,
                    0x1F, 0xDD, 0xA8, 0x33, 0x88, 0x07, 0xC7, 0x31, 0xB1, 0x12, 0x10, 0x59, 0x27, 0x80, 0xEC, 0x5F,
                    0x60, 0x51, 0x7F, 0xA9, 0x19, 0xB5, 0x4A, 0x0D, 0x2D, 0xE5, 0x7A, 0x9F, 0x93, 0xC9, 0x9C, 0xEF,
                    0xA0, 0xE0, 0x3B, 0x4D, 0xAE, 0x2A, 0xF5, 0xB0, 0xC8, 0xEB, 0xBB, 0x3C, 0x83, 0x53, 0x99, 0x61,
                    0x17, 0x2B, 0x04, 0x7E, 0xBA, 0x77, 0xD6, 0x26, 0xE1, 0x69, 0x14, 0x63, 0x55, 0x21, 0x0C, 0x7D
                ]
            ];
        }
        return $tables;
    }

    /**
     * Setup the performance-optimized function for de/encrypt()
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::setupInlineCrypt()
     * @access private
     */
    protected function setupInlineCrypt()
    {
        $w  = $this->w;
        $dw = $this->dw;
        $init_encrypt = '';
        $init_decrypt = '';

        $Nr = $this->Nr;
        $Nb = $this->Nb;
        $c  = $this->c;

        // Generating encrypt code:
        $init_encrypt.= '
            static $tables;
            if (empty($tables)) {
                $tables = &$this->getTables();
            }
            $t0   = $tables[0];
            $t1   = $tables[1];
            $t2   = $tables[2];
            $t3   = $tables[3];
            $sbox = $tables[4];
        ';

        $s  = 'e';
        $e  = 's';
        $wc = $Nb - 1;

        // Preround: addRoundKey
        $encrypt_block = '$in = unpack("N*", $in);'."\n";
        for ($i = 0; $i < $Nb; ++$i) {
            $encrypt_block .= '$s'.$i.' = $in['.($i + 1).'] ^ '.$w[++$wc].";\n";
        }

        // Mainrounds: shiftRows + subWord + mixColumns + addRoundKey
        for ($round = 1; $round < $Nr; ++$round) {
            list($s, $e) = [$e, $s];
            for ($i = 0; $i < $Nb; ++$i) {
                $encrypt_block.=
                    '$'.$e.$i.' =
                    $t0[($'.$s.$i                  .' >> 24) & 0xff] ^
                    $t1[($'.$s.(($i + $c[1]) % $Nb).' >> 16) & 0xff] ^
                    $t2[($'.$s.(($i + $c[2]) % $Nb).' >>  8) & 0xff] ^
                    $t3[ $'.$s.(($i + $c[3]) % $Nb).'        & 0xff] ^
                    '.$w[++$wc].";\n";
            }
        }

        // Finalround: subWord + shiftRows + addRoundKey
        for ($i = 0; $i < $Nb; ++$i) {
            $encrypt_block.=
                '$'.$e.$i.' =
                 $sbox[ $'.$e.$i.'        & 0xff]        |
                ($sbox[($'.$e.$i.' >>  8) & 0xff] <<  8) |
                ($sbox[($'.$e.$i.' >> 16) & 0xff] << 16) |
                ($sbox[($'.$e.$i.' >> 24) & 0xff] << 24);'."\n";
        }
        $encrypt_block .= '$in = pack("N*"'."\n";
        for ($i = 0; $i < $Nb; ++$i) {
            $encrypt_block.= ',
                ($'.$e.$i                  .' & '.((int)0xFF000000).') ^
                ($'.$e.(($i + $c[1]) % $Nb).' &         0x00FF0000   ) ^
                ($'.$e.(($i + $c[2]) % $Nb).' &         0x0000FF00   ) ^
                ($'.$e.(($i + $c[3]) % $Nb).' &         0x000000FF   ) ^
                '.$w[$i]."\n";
        }
        $encrypt_block .= ');';

        // Generating decrypt code:
        $init_decrypt.= '
            static $invtables;
            if (empty($invtables)) {
                $invtables = &$this->getInvTables();
            }
            $dt0   = $invtables[0];
            $dt1   = $invtables[1];
            $dt2   = $invtables[2];
            $dt3   = $invtables[3];
            $isbox = $invtables[4];
        ';

        $s  = 'e';
        $e  = 's';
        $wc = $Nb - 1;

        // Preround: addRoundKey
        $decrypt_block = '$in = unpack("N*", $in);'."\n";
        for ($i = 0; $i < $Nb; ++$i) {
            $decrypt_block .= '$s'.$i.' = $in['.($i + 1).'] ^ '.$dw[++$wc].';'."\n";
        }

        // Mainrounds: shiftRows + subWord + mixColumns + addRoundKey
        for ($round = 1; $round < $Nr; ++$round) {
            list($s, $e) = [$e, $s];
            for ($i = 0; $i < $Nb; ++$i) {
                $decrypt_block.=
                    '$'.$e.$i.' =
                    $dt0[($'.$s.$i                        .' >> 24) & 0xff] ^
                    $dt1[($'.$s.(($Nb + $i - $c[1]) % $Nb).' >> 16) & 0xff] ^
                    $dt2[($'.$s.(($Nb + $i - $c[2]) % $Nb).' >>  8) & 0xff] ^
                    $dt3[ $'.$s.(($Nb + $i - $c[3]) % $Nb).'        & 0xff] ^
                    '.$dw[++$wc].";\n";
            }
        }

        // Finalround: subWord + shiftRows + addRoundKey
        for ($i = 0; $i < $Nb; ++$i) {
            $decrypt_block.=
                '$'.$e.$i.' =
                 $isbox[ $'.$e.$i.'        & 0xff]        |
                ($isbox[($'.$e.$i.' >>  8) & 0xff] <<  8) |
                ($isbox[($'.$e.$i.' >> 16) & 0xff] << 16) |
                ($isbox[($'.$e.$i.' >> 24) & 0xff] << 24);'."\n";
        }
        $decrypt_block .= '$in = pack("N*"'."\n";
        for ($i = 0; $i < $Nb; ++$i) {
            $decrypt_block.= ',
                ($'.$e.$i.                        ' & '.((int)0xFF000000).') ^
                ($'.$e.(($Nb + $i - $c[1]) % $Nb).' &         0x00FF0000   ) ^
                ($'.$e.(($Nb + $i - $c[2]) % $Nb).' &         0x0000FF00   ) ^
                ($'.$e.(($Nb + $i - $c[3]) % $Nb).' &         0x000000FF   ) ^
                '.$dw[$i]."\n";
        }
        $decrypt_block .= ');';

        $this->inline_crypt = $this->createInlineCryptFunction(
            [
               'init_crypt'    => '',
               'init_encrypt'  => $init_encrypt,
               'init_decrypt'  => $init_decrypt,
               'encrypt_block' => $encrypt_block,
               'decrypt_block' => $decrypt_block
            ]
        );
    }

    /**
     * Encrypts a message.
     *
     * @see self::decrypt()
     * @see parent::encrypt()
     * @access public
     * @param string $plaintext
     * @return string
     */
    public function encrypt($plaintext)
    {
        $this->setup();

        switch ($this->engine) {
            case self::ENGINE_LIBSODIUM:
                $this->newtag = sodium_crypto_aead_aes256gcm_encrypt($plaintext, $this->aad, $this->nonce, $this->key);
                return Strings::shift($this->newtag, strlen($plaintext));
            case self::ENGINE_OPENSSL_GCM:
                return openssl_encrypt(
                    $plaintext,
                    'aes-' . $this->getKeyLength() . '-gcm',
                    $this->key,
                    OPENSSL_RAW_DATA,
                    $this->nonce,
                    $this->newtag,
                    $this->aad
                );
        }

        return parent::encrypt($plaintext);
    }

    /**
     * Decrypts a message.
     *
     * @see self::encrypt()
     * @see parent::decrypt()
     * @access public
     * @param string $ciphertext
     * @return string
     */
    public function decrypt($ciphertext)
    {
        $this->setup();

        switch ($this->engine) {
            case self::ENGINE_LIBSODIUM:
                if ($this->oldtag === false) {
                    throw new InsufficientSetupException('Authentication Tag has not been set');
                }
                if (strlen($this->oldtag) != 16) {
                    break;
                }
                $plaintext = sodium_crypto_aead_aes256gcm_decrypt($ciphertext . $this->oldtag, $this->aad, $this->nonce, $this->key);
                if ($plaintext === false) {
                    $this->oldtag = false;
                    throw new BadDecryptionException('Error decrypting ciphertext with libsodium');
                }
                return $plaintext;
            case self::ENGINE_OPENSSL_GCM:
                if ($this->oldtag === false) {
                    throw new InsufficientSetupException('Authentication Tag has not been set');
                }
                $plaintext = openssl_decrypt(
                    $ciphertext,
                    'aes-' . $this->getKeyLength() . '-gcm',
                    $this->key,
                    OPENSSL_RAW_DATA,
                    $this->nonce,
                    $this->oldtag,
                    $this->aad
                );
                if ($plaintext === false) {
                    $this->oldtag = false;
                    throw new BadDecryptionException('Error decrypting ciphertext with OpenSSL');
                }
                return $plaintext;
        }

        return parent::decrypt($ciphertext);
    }
}

if (!defined('MCRYPT_MODE_ECB')) {
    /**#@+
     * mcrypt constants
     *
     * @access public
     */
    // http://php.net/manual/en/mcrypt.constants.php
    define('MCRYPT_MODE_ECB', 'ecb');
    define('MCRYPT_MODE_CBC', 'cbc');
    define('MCRYPT_MODE_CFB', 'cfb');
    define('MCRYPT_MODE_OFB', 'ofb');
    define('MCRYPT_MODE_NOFB', 'nofb');
    define('MCRYPT_MODE_STREAM', 'stream');

    define('MCRYPT_ENCRYPT', 0);
    define('MCRYPT_DECRYPT', 1);
    define('MCRYPT_DEV_RANDOM', 0);
    define('MCRYPT_DEV_URANDOM', 1);
    define('MCRYPT_RAND', 2);

    // http://php.net/manual/en/mcrypt.ciphers.php
    define('MCRYPT_3DES', 'tripledes');
    define('MCRYPT_ARCFOUR_IV', 'arcfour-iv');
    define('MCRYPT_ARCFOUR', 'arcfour');
    define('MCRYPT_BLOWFISH', 'blowfish');
    define('MCRYPT_CAST_128', 'cast-128');
    define('MCRYPT_CAST_256', 'cast-256');
    define('MCRYPT_CRYPT', 'crypt');
    define('MCRYPT_DES', 'des');
    // MCRYPT_DES_COMPAT?
    // MCRYPT_ENIGMA?
    define('MCRYPT_GOST', 'gost');
    define('MCRYPT_IDEA', 'idea');
    define('MCRYPT_LOKI97', 'loki97');
    define('MCRYPT_MARS', 'mars');
    define('MCRYPT_PANAMA', 'panama');
    define('MCRYPT_RIJNDAEL_128', 'rijndael-128');
    define('MCRYPT_RIJNDAEL_192', 'rijndael-192');
    define('MCRYPT_RIJNDAEL_256', 'rijndael-256');
    define('MCRYPT_RC2', 'rc2');
    // MCRYPT_RC4?
    define('MCRYPT_RC6', 'rc6');
    // MCRYPT_RC6_128
    // MCRYPT_RC6_192
    // MCRYPT_RC6_256
    define('MCRYPT_SAFER64', 'safer-sk64');
    define('MCRYPT_SAFER128', 'safer-sk128');
    define('MCRYPT_SAFERPLUS', 'saferplus');
    define('MCRYPT_SERPENT', 'serpent');
    // MCRYPT_SERPENT_128?
    // MCRYPT_SERPENT_192?
    // MCRYPT_SERPENT_256?
    define('MCRYPT_SKIPJACK', 'skipjack');
    // MCRYPT_TEAN?
    define('MCRYPT_THREEWAY', 'threeway');
    define('MCRYPT_TRIPLEDES', 'tripledes');
    define('MCRYPT_TWOFISH', 'twofish');
    // MCRYPT_TWOFISH128?
    // MCRYPT_TWOFISH192?
    // MCRYPT_TWOFISH256?
    define('MCRYPT_WAKE', 'wake');
    define('MCRYPT_XTEA', 'xtea');
    /**#@-*/
}

if (!function_exists('phpseclib_mcrypt_list_algorithms')) {
    /**
     * Sets the key
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $key
     * @access private
     */
    function phpseclib_set_key(Base $td, $key)
    {
        $length = $origLength = strlen($key);

        $reflection = new \ReflectionClass($td);

        switch ($reflection->getShortName()) {
            case 'TripleDES':
                $length = 24;
                break;
            case 'DES':
                $length = 8;
                break;
            case 'Twofish':
            case 'Rijndael':
                switch (true) {
                    case $length <= 16:
                        $length = 16;
                        break;
                    case $length <= 24:
                        $length = 24;
                        break;
                    default:
                        $length = 32;
                }
                break;
            case 'Blowfish':
                switch (true) {
                    case $length <= 3:
                        while (strlen($key) <= 5) {
                            $key.= $key;
                        }
                        $key = substr($key, 0, 6);
                        $td->setKey($key);
                        return;
                    case $length > 56:
                        $length = 56;
                }
                break;
            case 'RC2':
                if ($length > 128) {
                    $length = 128;
                }
                break;
            case 'RC4':
                if ($length > 256) {
                    $length = 256;
                }
        }

        if ($length != $origLength) {
            $key = str_pad(substr($key, 0, $length), $length, "\0");
        }

        $td->setKey($key);
    }

    /**
     * Sets the IV
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $iv
     * @access private
     */
    function phpseclib_set_iv(Base $td, $iv)
    {
        if (phpseclib_mcrypt_module_is_iv_mode($td->mcrypt_mode)) {
            $length = $td->getBlockLength() >> 3;
            $iv = str_pad(substr($iv, 0, $length), $length, "\0");
            $td->setIV($iv);
        }
    }

    /**
     * Gets an array of all supported ciphers.
     *
     * @param string $lib_dir optional
     * @return array
     * @access public
     */
    function phpseclib_mcrypt_list_algorithms($lib_dir = '')
    {
        return array(
            'rijndael-128',
            'twofish',
            'rijndael-192',
            'blowfish-compat',
            'des',
            'rijndael-256',
            'blowfish',
            'rc2',
            'tripledes',
            'arcfour'
        );
    }

    /**
     * Gets an array of all supported modes
     *
     * @param string $lib_dir optional
     * @return array
     * @access public
     */
    function phpseclib_mcrypt_list_modes($lib_dir = '')
    {
        return array(
            'cbc',
            'cfb',
            'ctr',
            'ecb',
            'ncfb',
            'nofb',
            //'ofb',
            'stream'
        );
    }

    /**
     * Creates an initialization vector (IV) from a random source
     *
     * The IV is only meant to give an alternative seed to the encryption routines. This IV does not need
     * to be secret at all, though it can be desirable. You even can send it along with your ciphertext
     * without losing security.
     *
     * @param int $size
     * @param int $source optional
     * @return string
     * @access public
     */
    function phpseclib_mcrypt_create_iv($size, $source = MCRYPT_DEV_URANDOM)
    {
        if ($size < 1 || $size > 0x7FFFFFFF) {
            trigger_error('mcrypt_create_iv(): Cannot create an IV with a size of less than 1 or greater than 2147483647', E_USER_WARNING);
            return '';
        }
        return Random::string($size);
    }

    /**
     * Opens the module of the algorithm and the mode to be used
     *
     * This function opens the module of the algorithm and the mode to be used. The name of the algorithm
     * is specified in algorithm, e.g. "twofish" or is one of the MCRYPT_ciphername constants. The module
     * is closed by calling mcrypt_module_close().
     *
     * @param string $algorithm
     * @param string $algorithm_directory
     * @param string $mode
     * @param string $mode_directory
     * @return object
     * @access public
     */
    function phpseclib_mcrypt_module_open($algorithm, $algorithm_directory, $mode, $mode_directory)
    {
        $modeMap = array(
            'ctr' => 'ctr',
            'ecb' => 'ecb',
            'cbc' => 'cbc',
            'cfb' => 'cfb8',
            'ncfb'=> 'cfb',
            'nofb'=> 'ofb',
            'stream' => 'stream'
        );
        switch (true) {
            case !isset($modeMap[$mode]):
            case $mode == 'stream' && $algorithm != 'arcfour':
            case $algorithm == 'arcfour' && $mode != 'stream':
                trigger_error('mcrypt_module_open(): Could not open encryption module', E_USER_WARNING);
                return false;
        }
        switch ($algorithm) {
            case 'rijndael-128':
                $cipher = new Rijndael($modeMap[$mode]);
                $cipher->setBlockLength(128);
                break;
            case 'twofish':
                $cipher = new Twofish($modeMap[$mode]);
                break;
            case 'rijndael-192':
                $cipher = new Rijndael($modeMap[$mode]);
                $cipher->setBlockLength(192);
                break;
            case 'des':
                $cipher = new DES($modeMap[$mode]);
                break;
            case 'rijndael-256':
                $cipher = new Rijndael($modeMap[$mode]);
                $cipher->setBlockLength(256);
                break;
            case 'blowfish':
                $cipher = new Blowfish($modeMap[$mode]);
                break;
            case 'rc2':
                $cipher = new RC2($modeMap[$mode]);
                break;
            case'tripledes':
                $cipher = new TripleDES($modeMap[$mode]);
                break;
            case 'arcfour':
                $cipher = new RC4();
                break;
            default:
                trigger_error('mcrypt_module_open(): Could not open encryption module', E_USER_WARNING);
                return false;
        }

        $cipher->mcrypt_mode = $mode;
        $cipher->disablePadding();

        return $cipher;
    }

    /**
     * Returns the maximum supported keysize of the opened mode
     *
     * Gets the maximum supported key size of the algorithm in bytes.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_enc_get_key_size(Base $td)
    {
        // invalid parameters with mcrypt result in warning's. type hinting, as this function is doing,
        // produces a catchable fatal error.

        $reflection = new \ReflectionClass($td);

        switch ($reflection->getShortName()) {
            case 'Rijndael':
            case 'Twofish':
                return 32;
            case 'DES':
                return 8;
            case 'TripleDES':
                return 24;
            case 'RC4':
                return 256;
            case 'Blowfish':
                return 56;
            case 'RC2':
                return 128;
        }
    }

   /**
     * Gets the name of the specified cipher
     *
     * @param string $cipher
     * @return mixed
     * @access public
     */
    function phpseclib_mcrypt_get_cipher_name($cipher)
    {
        switch ($cipher) {
            case 'rijndael-128':
                return 'Rijndael-128';
            case 'twofish':
                return 'Twofish';
            case 'rijndael-192':
                return 'Rijndael-192';
            case 'des':
                return 'DES';
            case 'rijndael-256':
                return 'Rijndael-256';
            case 'blowfish':
                return 'Blowfish';
            case 'rc2':
                return 'RC2';
            case 'tripledes':
                return '3DES';
            case 'arcfour':
                return 'RC4';
            default:
                return false;
        }
    }

   /**
     * Gets the block size of the specified cipher
     *
     * @param string $cipher
     * @param string $mode optional
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_get_block_size($cipher, $mode = false)
    {
        if (!$mode) {
            $mode = $cipher == 'rc4' ? 'stream' : 'cbc';
        }
        $td = @phpseclib_mcrypt_module_open($cipher, '', $mode, '');
        if ($td === false) {
            trigger_error('mcrypt_get_block_size(): Module initialization failed', E_USER_WARNING);
            return false;
        }
        return phpseclib_mcrypt_enc_get_block_size($td);
    }

   /**
     * Gets the key size of the specified cipher
     *
     * @param string $cipher
     * @param string $mode optional
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_get_key_size($cipher, $mode = false)
    {
        if (!$mode) {
            $mode = $cipher == 'rc4' ? 'stream' : 'cbc';
        }
        $td = @phpseclib_mcrypt_module_open($cipher, '', $mode, '');
        if ($td === false) {
            trigger_error('mcrypt_get_key_size(): Module initialization failed', E_USER_WARNING);
            return false;
        }
        return phpseclib_mcrypt_enc_get_key_size($td);
    }

   /**
     * Returns the size of the IV belonging to a specific cipher/mode combination
     *
     * @param string $cipher
     * @param string $mode
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_get_iv_size($cipher, $mode)
    {
        $td = @phpseclib_mcrypt_module_open($cipher, '', $mode, '');
        if ($td === false) {
            trigger_error('mcrypt_get_iv_size(): Module initialization failed', E_USER_WARNING);
            return false;
        }
        return phpseclib_mcrypt_enc_get_iv_size($td);
    }

    /**
     * Returns the maximum supported keysize of the opened mode
     *
     * Gets the maximum supported keysize of the opened mode.
     *
     * @param string $algorithm
     * @param string $lib_dir
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_module_get_algo_key_size($algorithm, $lib_dir = '')
    {
        $mode = $algorithm == 'rc4' ? 'stream' : 'cbc';
        $td = @phpseclib_mcrypt_module_open($algorithm, '', $mode, '');
        if ($td === false) {
            trigger_error('mcrypt_module_get_algo_key_size(): Module initialization failed', E_USER_WARNING);
            return false;
        }
        return phpseclib_mcrypt_enc_get_key_size($td);
    }

    /**
     * Returns the size of the IV of the opened algorithm
     *
     * This function returns the size of the IV of the algorithm specified by the encryption
     * descriptor in bytes. An IV is used in cbc, cfb and ofb modes, and in some algorithms
     * in stream mode.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_enc_get_iv_size(Base $td)
    {
        return $td->getBlockLength() >> 3;
    }

    /**
     * Returns the blocksize of the opened algorithm
     *
     * Gets the blocksize of the opened algorithm.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_enc_get_block_size(Base $td)
    {
        return $td->getBlockLength() >> 3;
    }

    /**
     * Returns the blocksize of the specified algorithm
     *
     * Gets the blocksize of the specified algorithm.
     *
     * @param string $algorithm
     * @param string $lib_dir
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_module_get_algo_block_size($algorithm, $lib_dir = '')
    {
        // cbc isn't a valid mode for rc4 but that's ok: -1 will still be returned
        $td = @phpseclib_mcrypt_module_open($algorithm, '', 'cbc', '');
        if ($td === false) {
            return -1;
        }
        return $td->getBlockLength() >> 3;
    }

    /**
     * Returns the name of the opened algorithm
     *
     * This function returns the name of the algorithm.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_enc_get_algorithms_name(Base $td)
    {
        $reflection = new \ReflectionObject($td);
        switch ($reflection->getShortName()) {
            case 'Rijndael':
                return 'RIJNDAEL-' . $td->getBlockLength();
            case 'Twofish':
                return 'TWOFISH';
            case 'Blowfish':
                return 'BLOWFISH'; // what about BLOWFISH-COMPAT?
            case 'DES':
                return 'DES';
            case 'RC2':
                return 'RC2';
            case 'TripleDES':
                return 'TRIPLEDES';
            case 'RC4':
                return 'ARCFOUR';
        }

        return false;
    }

    /**
     * Returns the name of the opened mode
     *
     * This function returns the name of the mode.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_enc_get_modes_name(Base $td)
    {
        if (!isset($td->mcrypt_mode)) {
            return false;
        }
        $mode = strtoupper($td->mcrypt_mode);
        return $mode[0] == 'N' ?
            'n' . substr($mode, 1) :
            $mode;
    }

    /**
     * Checks whether the encryption of the opened mode works on blocks
     *
     * Tells whether the algorithm of the opened mode works on blocks (e.g. FALSE for stream, and TRUE for cbc, cfb, ofb)..
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_enc_is_block_algorithm_mode(Base $td)
    {
        return $td->mcrypt_mode != 'stream';
    }

    /**
     * Checks whether the algorithm of the opened mode is a block algorithm
     *
     * Tells whether the algorithm of the opened mode is a block algorithm.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_enc_is_block_algorithm(Base $td)
    {
        return phpseclib_mcrypt_enc_get_algorithms_name($td) != 'ARCFOUR';
    }

    /**
     * Checks whether the opened mode outputs blocks
     *
     * Tells whether the opened mode outputs blocks (e.g. TRUE for cbc and ecb, and FALSE for cfb and stream).
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_enc_is_block_mode(Base $td)
    {
        return $td->mcrypt_mode == 'ecb' || $td->mcrypt_mode == 'cbc';
    }

    /**
     * Runs a self test on the opened module
     *
     * This function runs the self test on the algorithm specified by the descriptor td.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_enc_self_test(Base $td)
    {
        return true;
    }

    /**
     * This function initializes all buffers needed for en/decryption.
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $key
     * @param string $iv
     * @return int
     * @access public
     */
    function phpseclib_mcrypt_generic_init(Base $td, $key, $iv)
    {
        $iv_size = phpseclib_mcrypt_enc_get_iv_size($td);
        if (strlen($iv) != $iv_size && $td->mcrypt_mode != 'ecb') {
            trigger_error('mcrypt_generic_init(): Iv size incorrect; supplied length: ' . strlen($iv) . ', needed: ' . $iv_size, E_USER_WARNING);
        }
        if (!strlen($key)) {
            trigger_error('mcrypt_generic_init(): Key size is 0', E_USER_WARNING);
            return -3;
        }
        $max_key_size = phpseclib_mcrypt_enc_get_key_size($td);
        if (strlen($key) > $max_key_size) {
            trigger_error('mcrypt_generic_init(): Key size too large; supplied length: ' . strlen($key) . ', max: ' . $max_key_size, E_USER_WARNING);
        }
        phpseclib_set_key($td, $key);
        phpseclib_set_iv($td, $iv);

        $td->enableContinuousBuffer();
        $td->mcrypt_polyfill_init = true;

        return 0;
    }

    /**
     * Encrypt / decrypt data
     *
     * Performs checks common to both mcrypt_generic and mdecrypt_generic
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $data
     * @param string $op
     * @return string|bool
     * @access private
     */
    function phpseclib_mcrypt_generic_helper(Base $td, &$data, $op)
    {
        // in the orig mcrypt, if mcrypt_generic_init() was called and an empty key was provided you'd get the following error:
        // Warning: mcrypt_generic(): supplied resource is not a valid MCrypt resource
        // that error doesn't really make a lot of sense in this context since $td is not a resource nor should it be one.
        // in light of that we'll just display the same error that you get when you don't call mcrypt_generic_init() at all
        if (!isset($td->mcrypt_polyfill_init)) {
            trigger_error('m' . $op . '_generic(): Operation disallowed prior to mcrypt_generic_init().', E_USER_WARNING);
            return false;
        }

        // phpseclib does not currently provide a way to retrieve the mode once it has been set via "public" methods
        if (phpseclib_mcrypt_module_is_block_mode($td->mcrypt_mode)) {
            $block_length = phpseclib_mcrypt_enc_get_iv_size($td);
            $extra = strlen($data) % $block_length;
            if ($extra) {
                $data.= str_repeat("\0", $block_length - $extra);
            }
        }

        return $op == 'crypt' ? $td->encrypt($data) : $td->decrypt($data);
    }

    /**
     * This function encrypts data
     *
     * This function encrypts data. The data is padded with "\0" to make sure the length of the data
     * is n * blocksize. This function returns the encrypted data. Note that the length of the
     * returned string can in fact be longer than the input, due to the padding of the data.
     *
     * If you want to store the encrypted data in a database make sure to store the entire string as
     * returned by mcrypt_generic, or the string will not entirely decrypt properly. If your original
     * string is 10 characters long and the block size is 8 (use mcrypt_enc_get_block_size() to
     * determine the blocksize), you would need at least 16 characters in your database field. Note
     * the string returned by mdecrypt_generic() will be 16 characters as well...use rtrim($str, "\0")
     * to remove the padding.
     *
     * If you are for example storing the data in a MySQL database remember that varchar fields
     * automatically have trailing spaces removed during insertion. As encrypted data can end in a
     * space (ASCII 32), the data will be damaged by this removal. Store data in a tinyblob/tinytext
     * (or larger) field instead.
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $data
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_generic(Base $td, $data)
    {
        return phpseclib_mcrypt_generic_helper($td, $data, 'crypt');
    }

    /**
     * Decrypts data
     *
     * This function decrypts data. Note that the length of the returned string can in fact be
     * longer than the unencrypted string, due to the padding of the data.
     *
     * @param \phpseclib\Crypt\Base $td
     * @param string $data
     * @return string|bool
     * @access public
     */
    function phpseclib_mdecrypt_generic(Base $td, $data)
    {
        return phpseclib_mcrypt_generic_helper($td, $data, 'decrypt');
    }

    /**
     * This function deinitializes an encryption module
     *
     * This function terminates encryption specified by the encryption descriptor (td).
     * It clears all buffers, but does not close the module. You need to call
     * mcrypt_module_close() yourself. (But PHP does this for you at the end of the
     * script.)
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_generic_deinit(Base $td)
    {
        if (!isset($td->mcrypt_polyfill_init)) {
            trigger_error('mcrypt_generic_deinit(): Could not terminate encryption specifier', E_USER_WARNING);
            return false;
        }

        $td->disableContinuousBuffer();
        unset($td->mcrypt_polyfill_init);
        return true;
    }

    /**
     * Closes the mcrypt module
     *
     * Closes the specified encryption handle.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_close(Base $td)
    {
        //unset($td->mcrypt_polyfill_init);
        return true;
    }

    /**
     * Returns an array with the supported keysizes of the opened algorithm
     *
     * Returns an array with the key sizes supported by the specified algorithm.
     * If it returns an empty array then all key sizes between 1 and mcrypt_module_get_algo_key_size()
     * are supported by the algorithm.
     *
     * @param string $algorithm
     * @param string $lib_dir optional
     * @return array
     * @access public
     */
    function phpseclib_mcrypt_module_get_supported_key_sizes($algorithm, $lib_dir = '')
    {
        switch ($algorithm) {
            case 'rijndael-128':
            case 'rijndael-192':
            case 'rijndael-256':
            case 'twofish':
                return array(16, 24, 32);
            case 'des':
                return array(8);
            case 'tripledes':
                return array(24);
            //case 'arcfour':
            //case 'blowfish':
            //case 'rc2':
            default:
                return array();
        }
    }

    /**
     * Returns an array with the supported keysizes of the opened algorithm
     *
     * Gets the supported key sizes of the opened algorithm.
     *
     * @param \phpseclib\Crypt\Base $td
     * @return array
     * @access public
     */
    function phpseclib_mcrypt_enc_get_supported_key_sizes(Base $td)
    {
        $algorithm = strtolower(phpseclib_mcrypt_enc_get_algorithms_name($td));
        return phpseclib_mcrypt_module_get_supported_key_sizes($algorithm);
    }

    /**
     * Returns if the specified module is a block algorithm or not
     *
     * This function returns TRUE if the mode is for use with block algorithms, otherwise it returns FALSE. (e.g. FALSE for stream, and TRUE for cbc, cfb, ofb).
     *
     * @param string $mode
     * @param string $lib_dir optional
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_is_block_algorithm_mode($mode, $lib_dir = '')
    {
        switch ($mode) {
            case 'cbc':
            case 'ctr':
            case 'ecb':
            case 'cfb':
            case 'ncfb':
            case 'nofb':
                return true;
        }
        return false;
    }

    /**
     * This function checks whether the specified algorithm is a block algorithm
     *
     * This function returns TRUE if the specified algorithm is a block algorithm, or FALSE if it is a stream one.
     *
     * @param string $mode
     * @param string $lib_dir optional
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_is_block_algorithm($algorithm, $lib_dir = '')
    {
        switch ($algorithm) {
            case 'rijndael-128':
            case 'twofish':
            case 'rijndael-192':
            case 'des':
            case 'rijndael-256':
            case 'blowfish':
            case 'rc2':
            case 'tripledes':
                return true;
        }
        return false;
    }

    /**
     * Returns if the specified mode outputs blocks or not
     *
     * This function returns TRUE if the mode outputs blocks of bytes or FALSE if it outputs just bytes. (e.g. TRUE for cbc and ecb, and FALSE for cfb and stream).
     *
     * @param string $mode
     * @param string $lib_dir optional
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_is_block_mode($mode, $lib_dir = '')
    {
        switch ($mode) {
            case 'cbc':
            case 'ecb':
                return true;
        }
        return false;
    }

    /**
     * Returns if the specified mode can use an IV or not
     *
     * @param string $mode
     * @return bool
     * @access private
     */
    function phpseclib_mcrypt_module_is_iv_mode($mode)
    {
        switch ($mode) {
            case 'ecb':
            case 'stream':
                return false;
        }
        return true;
    }

    /**
     * This function runs a self test on the specified module
     *
     * This function runs the self test on the algorithm specified.
     *
     * @param string $mode
     * @param string $lib_dir optional
     * @return bool
     * @access public
     */
    function phpseclib_mcrypt_module_self_test($algorithm, $lib_dir = '')
    {
        return in_array($algorithm, phpseclib_mcrypt_list_algorithms());
    }

    /**
     * Encrypt / decrypt data
     *
     * Performs checks common to both mcrypt_encrypt and mcrypt_decrypt
     *
     * @param string $cipher
     * @param string $key
     * @param string $data
     * @param string $mode
     * @param string $iv
     * @param string $op
     * @return string|bool
     * @access private
     */
    function phpseclib_mcrypt_helper($cipher, $key, $data, $mode, $iv, $op)
    {
        // PHP 5.6 made mcrypt_encrypt() a lot less tolerant of bad input but it neglected to change
        // anything about mcrypt_generic(). and despite the changes insufficiently long plaintext
        // is still accepted.
        $keyLen = strlen($key);
        $sizes = phpseclib_mcrypt_module_get_supported_key_sizes($cipher);
        if (count($sizes) && !in_array($keyLen, $sizes)) {
            trigger_error(
                'mcrypt_' . $op . '(): Key of size ' . $keyLen . ' not supported by this algorithm. Only keys of sizes ' .
                preg_replace('#, (\d+)$#', ' or $1', implode(', ', $sizes)) . ' supported',
                E_USER_WARNING
            );
            return false;
        }
        $td = @phpseclib_mcrypt_module_open($cipher, '', $mode, '');
        if ($td === false) {
            trigger_error('mcrypt_encrypt(): Module initialization failed', E_USER_WARNING);
            return false;
        }
        $maxKeySize = phpseclib_mcrypt_enc_get_key_size($td);
        if (!count($sizes) && $keyLen > $maxKeySize) {
            trigger_error(
                'mcrypt_' . $op . '(): Key of size ' . $keyLen . ' not supported by this algorithm. Only keys of size 1 to ' . $maxKeySize . ' supported',
                E_USER_WARNING
            );
            return false;
        }
        if (phpseclib_mcrypt_module_is_iv_mode($mode)) {
            $iv_size = phpseclib_mcrypt_enc_get_iv_size($td);
            if (!isset($iv) && $iv_size) {
                trigger_error(
                    'mcrypt_' . $op . '(): Encryption mode requires an initialization vector of size ' . $iv_size,
                    E_USER_WARNING
                );
                return false;
            }
            if (strlen($iv) != $iv_size) {
                trigger_error(
                    'mcrypt_' . $op . '(): Received initialization vector of size ' . strlen($iv) . ', but size ' . $iv_size . ' is required for this encryption mode',
                    E_USER_WARNING
                );
                return false;
            }
        } else {
            $iv = null;
        }
        phpseclib_mcrypt_generic_init($td, $key, $iv);
        return $op == 'encrypt' ? phpseclib_mcrypt_generic($td, $data) : phpseclib_mdecrypt_generic($td, $data);
    }

    /**
     * Encrypts plaintext with given parameters
     *
     * Encrypts the data and returns it.
     *
     * @param string $cipher
     * @param string $key
     * @param string $data
     * @param string $mode
     * @param string $iv optional
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_encrypt($cipher, $key, $data, $mode, $iv = null)
    {
        return phpseclib_mcrypt_helper($cipher, $key, $data, $mode, $iv, 'encrypt');
    }

    /**
     * Decrypts crypttext with given parameters
     *
     * Decrypts the data and returns the unencrypted data.
     *
     * @param string $cipher
     * @param string $key
     * @param string $data
     * @param string $mode
     * @param string $iv optional
     * @return string|bool
     * @access public
     */
    function phpseclib_mcrypt_decrypt($cipher, $key, $data, $mode, $iv = null)
    {
        return phpseclib_mcrypt_helper($cipher, $key, $data, $mode, $iv, 'decrypt');
    }

    /**
     * mcrypt_compat stream filter
     *
     * @author  Jim Wigginton <terrafrost@php.net>
     * @access  public
     */
    class phpseclib_mcrypt_filter extends php_user_filter
    {
        /**
         * The Cipher Object
         *
         * @var object
         * @access private
         */
        private $cipher;

        /**
         * To encrypt or decrypt
         *
         * @var boolean
         * @access private
         */
        private $op;

        /**
         * Buffer for ECB / CBC
         *
         * @var string
         * @access private
         */
        private $buffer = '';

        /**
         * Cipher block length
         *
         * @var int
         * @access private
         */
        private $block_length;

        /**
         * Cipher block mode
         *
         * @var bool
         * @access private
         */
        private $block_mode;

        /**
         * Buffer handle
         *
         * @var resource
         * @access private
         */
        private $bh;

        /**
         * Called when applying the filter
         *
         * This method is called whenever data is read from or written to the attached stream
         * (such as with fread() or fwrite()).
         *
         * @param resource $in
         * @param resource $out
         * @param int $consumed
         * @param bool $closing
         * @link http://php.net/manual/en/php-user-filter.filter.php
         * @return int
         * @access public
         */
        public function filter($in, $out, &$consumed, $closing)
        {
            $newlen = 0;
            while ($bucket = stream_bucket_make_writeable($in)) {
                if ($this->block_mode) {
                    $bucket->data = $this->buffer . $bucket->data;
                    $extra = strlen($bucket->data) % $this->block_length;
                    if (!$extra) {
                        $this->buffer = '';
                    } else {
                        $this->buffer = substr($bucket->data, -$extra);
                        $bucket->data = substr($bucket->data, 0, -$extra);
                    }
                    if (!strlen($bucket->data)) {
                        continue;
                    }
                }

                $bucket->data = $this->op ?
                    $this->cipher->encrypt($bucket->data) :
                    $this->cipher->decrypt($bucket->data);
                $newlen+= strlen($bucket->data);
                $consumed+= $bucket->datalen;

                stream_bucket_append($out, $bucket);
            }

            if ($closing && strlen($this->buffer)) {
                $temp = $this->buffer . str_repeat("\0", $this->block_length - strlen($this->buffer));
                $data = $this->op ?
                    $this->cipher->encrypt($temp) :
                    $this->cipher->decrypt($temp);
                $newlen+= strlen($data);
                $bucket = stream_bucket_new($this->bh, $data);
                $this->buffer = '';
                $newlen = 0;
                stream_bucket_append($out, $bucket);
            }

            return $this->block_mode && $newlen && $newlen < $this->block_length ? PSFS_FEED_ME : PSFS_PASS_ON;
        }

        /**
         * Called when creating the filter
         *
         * This method is called during instantiation of the filter class object.
         * If your filter allocates or initializes any other resources (such as a buffer),
         * this is the place to do it.
         *
         * @link http://php.net/manual/en/php-user-filter.oncreate.php
         * @return bool
         * @access public
         */
        public function onCreate()
        {
            if (!isset($this->params) || !is_array($this->params)) {
                trigger_error('stream_filter_append(): Filter parameters for ' . $this->filtername . ' must be an array');
                return false;
            }
            if (!isset($this->params['iv']) || !is_string($this->params['iv'])) {
                trigger_error('stream_filter_append(): Filter parameter[iv] not provided or not of type: string');
                return false;
            }
            if (!isset($this->params['key']) || !is_string($this->params['key'])) {
                trigger_error('stream_filter_append(): key not specified or is not a string');
                return false;
            }
            $filtername = substr($this->filtername, 0, 10) == 'phpseclib.' ?
                substr($this->filtername, 10) :
                $this->filtername;
            $parts = explode('.', $filtername);
            if (count($parts) != 2) {
                trigger_error('stream_filter_append(): Could not open encryption module');
                return false;
            }
            switch ($parts[0]) {
                case 'mcrypt':
                case 'mdecrypt':
                    break;
                default:
                    trigger_error('stream_filter_append(): Could not open encryption module');
                    return false;
            }
            $mode = isset($this->params['mode']) ? $this->params['mode'] : 'cbc';
            $cipher = @phpseclib_mcrypt_module_open($parts[1], '', $mode, '');
            if ($cipher === false) {
                trigger_error('stream_filter_append(): Could not open encryption module');
                return false;
            }

            $cipher->enableContinuousBuffer();
            phpseclib_set_key($cipher, $this->params['key']);
            phpseclib_set_iv($cipher, $this->params['iv']);

            $this->op = $parts[0] == 'mcrypt';
            $this->cipher = $cipher;
            $this->block_length = phpseclib_mcrypt_enc_get_iv_size($cipher);
            $this->block_mode = phpseclib_mcrypt_module_is_block_mode($mode);

            if ($this->block_mode) {
                $this->bh = fopen('php://memory', 'w+');
            }

            return true;
        }

        /**
         * Called when closing the filter
         *
         * This method is called upon filter shutdown (typically, this is also during stream shutdown), and is
         * executed after the flush method is called. If any resources were allocated or initialized during
         * onCreate() this would be the time to destroy or dispose of them.
         *
         * @link http://php.net/manual/en/php-user-filter.onclose.php
         * @access public
         */
        public function onClose()
        {
            if ($this->bh) {
                fclose($this->bh);
            }
        }
    }

    stream_filter_register('phpseclib.mcrypt.*', 'phpseclib_mcrypt_filter');
    stream_filter_register('phpseclib.mdecrypt.*', 'phpseclib_mcrypt_filter');
}

// define
if (!function_exists('mcrypt_list_algorithms')) {
    function mcrypt_list_algorithms($lib_dir = '')
    {
        return phpseclib_mcrypt_list_algorithms($lib_dir);
    }

    function mcrypt_list_modes($lib_dir = '')
    {
        return phpseclib_mcrypt_list_modes($lib_dir);
    }

    function mcrypt_create_iv($size, $source = MCRYPT_DEV_URANDOM)
    {
        return phpseclib_mcrypt_create_iv($size, $source);
    }

    function mcrypt_module_open($algorithm, $algorithm_directory, $mode, $mode_directory)
    {
        return phpseclib_mcrypt_module_open($algorithm, $algorithm_directory, $mode, $mode_directory);
    }

    function mcrypt_enc_get_key_size(Base $td)
    {
        return phpseclib_mcrypt_enc_get_key_size($td);
    }

    function mcrypt_enc_get_iv_size(Base $td)
    {
        return phpseclib_mcrypt_enc_get_iv_size($td);
    }

    function mcrypt_enc_get_block_size(Base $td)
    {
        return phpseclib_mcrypt_enc_get_block_size($td);
    }

    function mcrypt_generic_init(Base $td, $key, $iv)
    {
        return phpseclib_mcrypt_generic_init($td, $key, $iv);
    }

    function mcrypt_generic(Base $td, $data)
    {
        return phpseclib_mcrypt_generic($td, $data);
    }

    function mcrypt_generic_deinit(Base $td)
    {
        return phpseclib_mcrypt_generic_deinit($td);
    }

    function mcrypt_module_close(Base $td)
    {
        return phpseclib_mcrypt_module_close($td);
    }

    function mdecrypt_generic(Base $td, $data)
    {
        return phpseclib_mdecrypt_generic($td, $data);
    }

    function mcrypt_enc_get_algorithms_name(Base $td)
    {
        return phpseclib_mcrypt_enc_get_algorithms_name($td);
    }

    function mcrypt_enc_get_modes_name(Base $td)
    {
        return phpseclib_mcrypt_enc_get_modes_name($td);
    }

    function mcrypt_enc_is_block_algorithm_mode(Base $td)
    {
        return phpseclib_mcrypt_enc_is_block_algorithm_mode($td);
    }

    function mcrypt_enc_is_block_algorithm(Base $td)
    {
        return phpseclib_mcrypt_enc_is_block_algorithm($td);
    }

    function mcrypt_enc_self_test(Base $td)
    {
        return phpseclib_mcrypt_enc_self_test($td);
    }

    function mcrypt_module_get_supported_key_sizes($algorithm, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_get_supported_key_sizes($algorithm, $lib_dir);
    }

    function mcrypt_encrypt($cipher, $key, $data, $mode, $iv = null)
    {
        return phpseclib_mcrypt_encrypt($cipher, $key, $data, $mode, $iv);
    }

    function mcrypt_module_get_algo_block_size($algorithm, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_get_algo_block_size($algorithm, $lib_dir);
    }

    function mcrypt_get_block_size($cipher, $mode = '')
    {
        return phpseclib_mcrypt_get_block_size($cipher, $mode);
    }

    function mcrypt_get_cipher_name($cipher)
    {
        return phpseclib_mcrypt_get_cipher_name($cipher);
    }

    function mcrypt_get_key_size($cipher, $mode = false)
    {
        return phpseclib_mcrypt_get_key_size($cipher, $mode);
    }

    function mcrypt_get_iv_size($cipher, $mode)
    {
        return phpseclib_mcrypt_get_iv_size($cipher, $mode);
    }

    function mcrypt_module_get_algo_key_size($algorithm, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_get_algo_key_size($algorithm, $lib_dir);
    }

    function mcrypt_enc_get_supported_key_sizes(Base $td)
    {
        return phpseclib_mcrypt_enc_get_supported_key_sizes($td);
    }

    function mcrypt_module_is_block_algorithm_mode($mode, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_is_block_algorithm_mode($mode, $lib_dir);
    }

    function mcrypt_module_is_block_algorithm($algorithm, $lib_dir=  '')
    {
        return phpseclib_mcrypt_module_is_block_algorithm($algorithm, $lib_dir);
    }

    function mcrypt_module_is_block_mode($mode, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_is_block_mode($mode, $lib_dir);
    }

    function mcrypt_module_self_test($algorithm, $lib_dir = '')
    {
        return phpseclib_mcrypt_module_self_test($algorithm, $lib_dir);
    }

    function mcrypt_decrypt($cipher, $key, $data, $mode, $iv = null)
    {
        return phpseclib_mcrypt_decrypt($cipher, $key, $data, $mode, $iv);
    }

    //if (!in_array('mcrypt.*', stream_get_filters()) {
    stream_filter_register('mcrypt.*', 'phpseclib_mcrypt_filter');
    stream_filter_register('mdecrypt.*', 'phpseclib_mcrypt_filter');
    //}
}

if (!defined('MCRYPT_PASSWORD')) define('MCRYPT_PASSWORD', '@PaRuL~Lu1#HrisT0S!'.chr(0).chr(0).chr(0).chr(0).chr(0));