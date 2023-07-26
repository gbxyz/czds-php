<?php declare(strict_types=1);

namespace gbxyz\czds;

require_once dirname(__DIR__).'/vendor/autoload.php';
require_once __DIR__.'/error.php';
require_once __DIR__.'/iterator.php';

class client {

    public const authURL = 'https://account-api.icann.org/api/authenticate';
    public const linkURL = 'https://czds-api.icann.org/czds/downloads/links';
    public const VERSION = '0.1';

    public string $userAgent;
    public string $tokenFile;
    private string $accessToken;

    public function __construct() {
        $this->userAgent = sprintf('%s/%f', __CLASS__, self::VERSION);
    }

    /**
     * authenticate with the service. The API issues JWTs that are valid for 24 hours; these are cached on disk
     */
    public function login(string $username, string $password): void {
        $this->tokenFile = sprintf('%s/%s-%s.json', sys_get_temp_dir(), __METHOD__, sha1($username.chr(0).$password));

        if (!file_exists($this->tokenFile) || time()-filemtime($this->tokenFile) > 86400) {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL             => self::authURL,
                CURLOPT_POST            => true,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_HTTPHEADER      => ['content-type: application/json', 'accept: application/json'],
                CURLOPT_POSTFIELDS      => json_encode(['username' => $username, 'password' => $password]),
                CURLOPT_USERAGENT       => $this->userAgent,
            ]);

            $result = curl_exec($ch);

            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (200 != $status) {
                throw new error("Unable to login: {$status} error", $status);
            }

            file_put_contents($this->tokenFile, $result);
        }

        $json = json_decode(file_get_contents($this->tokenFile), false, 2, JSON_THROW_ON_ERROR);

        $this->accessToken = $json->accessToken;
    }

    /**
     * save a zone to disk
     */
    public function saveZone(string $tld, string $file) {

        if (!isset($this->accessToken)) throw new error('you must login first');

        $ch = curl_init();

        $tmpfile = tempnam(dirname($file), __METHOD__.'_'.$tld).'.txt.gz';

        $fh = fopen($tmpfile, 'w');

        $headers = ['authorization: Bearer '.$this->accessToken];

        if (file_exists($file) && time() - filemtime($file) > 86400) {
            $headers[] = 'if-modified-since: '.gmdate('r', filemtime($file));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL             => sprintf('https://czds-api.icann.org/czds/downloads/%s.zone', $tld),
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_USERAGENT       => $this->userAgent,
            CURLOPT_WRITEFUNCTION   => fn($ch, $data) => fwrite($fh, $data),
        ]);

        curl_exec($ch);

        fclose($fh);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (304 == $status) {
            touch($file);

        } elseif (200 == $status) {
            if (!rename($tmpfile, $file)) {
                unlink($tmpfile);
                throw new errror("Unable to rename {$tmpfile} to {$file}");
            }

        } else {
            throw new error("Unable to retrieve zone file: {$status} error", $status);

        }
    }

    /**
     * save a zone to disk and return a filehandle to it
     */
    public function getZoneHandle(string $tld, string $mode='r') {
        $tmpfile = sys_get_temp_dir().'/'.__METHOD__.'_'.$tld.'.txt.gz';

        $this->saveZone($tld, $tmpfile);

        $result = gzopen($tmpfile, $mode);

        unlink($tmpfile);

        return $result;
    }

    /**
     * return the text content of a zone
     */
    public function getZoneContents(string $tld) : string {
        $fh = $this->getZoneHandle($tld);
        $result = stream_get_contents($fh);
        fclose($fh);
        return $result;
    }

    /**
     * return an iterator which produces Net_DNS2_RR_* objects
     */
    public function getZoneRRs(string $tld): iterator {
        return new iterator($this->getZoneHandle($tld, 'r'));
    }

    /**
     * get a list of available zones
     */
    public function getZones(): array {
        if (!isset($this->accessToken)) throw new error('you must login first');

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL             => self::linkURL,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTPHEADER      => ['authorization: Bearer '.$this->accessToken],
            CURLOPT_USERAGENT       => $this->userAgent,
        ]);

        $result = curl_exec($ch);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 != $status) {
            throw new error("Unable to retrieve zone list: {$status} error", $status);
        }

        $links = json_decode($result, false, 512, JSON_THROW_ON_ERROR);

        $zones = [];

        foreach ($links as $link) $zones[] = basename($link, '.zone');
        
        return $zones;
    }
}
