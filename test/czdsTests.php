<?php

declare(strict_types=1);

class czdsTests extends PHPUnit\Framework\TestCase {

    private static string $username;
    private static string $password;
    private static string $zone;
    private static gbxyz\czds\client $client;

    public static function setUpBeforeClass(): void {
        $config = __DIR__.'/config.ini';
        if (!file_exists($config)) {
            file_put_contents($config, "[czds]\nusername=\npassword=\nzone=\n");
            throw new Exception("Config file '{$config}' not found. A template has been saved to that location.");

        } else {
            $result = parse_ini_file($config, true);
            if (!is_array($result)) {
                throw new Exception("Error parsing config file '{$config}'");
            }

            if (!isset($result['czds'])) {
                throw new Exception("Missing [czds] section in '{$config}'");
            }

            self::$username = $result['czds']['username'] ?? throw new Exception("Missing username");
            self::$password = $result['czds']['password'] ?? throw new Exception("Missing password");
            self::$zone     = $result['czds']['zone'] ?? throw new Exception("Missing zone");
        }
    }

    public static function tearDownAfterClass(): void {
        unlink(self::$client->tokenFile);
    }

    public function testIntegration(): void {
        require_once dirname(__DIR__).'/src/client.php';
        $this->assertTrue(true);
    }

    public function testInstantiation(): void {
        self::$client = new gbxyz\czds\client;
        $this->assertTrue(true);
    }

    public function testLogin(): void {
        self::$client->login(self::$username, self::$password);
    }

    public function testSaveZone(): void {
        $file = tempnam(sys_get_temp_dir(), __METHOD__.uniqid());

        self::$client->saveZone(self::$zone, $file);
        $this->assertFileExists($file);
        $this->assertGreaterThan(0, filesize($file));

        unlink($file);
    }

    public function testGetZoneHandle(): void {
        $tmp = fopen('/dev/null', 'w');

        $fh = self::$client->getZoneHandle(self::$zone);
        $this->assertIsResource($fh);

        $bytes = stream_copy_to_stream($fh, $tmp);
        $this->assertGreaterThan(0, $bytes);

        fclose($fh);
    }

    public function testGetZoneContents(): void {
        $this->assertNotEmpty(self::$client->getZoneContents(self::$zone));
    }

    public function testGetZoneRRs(): void {
        $result = self::$client->getZoneRRs(self::$zone);

        $this->assertIsObject($result);
        $this->assertInstanceOf('gbxyz\\czds\\iterator', $result);

        foreach ($result as $rr) {
            $this->assertIsObject($rr);
            $this->assertInstanceOf('Net_DNS2_RR', $rr);
        }
    }

    public function testGetZones(): void {
        $result = self::$client->getZones();
        $this->assertIsArray($result);
        $this->assertNotCount(0, $result);

        foreach ($result as $tld) {
            $this->assertMatchesRegularExpression('/^[a-z0-9\-]{3,}/', $tld);
        }

        $this->assertContains(self::$zone, $result);
    }
}
