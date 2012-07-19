<?

/*
 *  HOW TO USE 
 * 
 * // configure profiler
 * // set  profiler server:port  and  set Group for timers 
 * PRFLR::init('localhost','4000','testApp');
 * 
 * 
 * //start timer
 * PRFLR::Begin('mongoDB.save');
 * 
 * //some code
 * sleep(1000);
 * 
 * //stop timer
 * PRFLR::End('mongoDB.save');
 * 
 */

class PRFLR {

    private static $sender;

    public static function init($server, $port, $group) {
        self::$sender = new PRFLRSender();
        self::$sender->server = $server;
        self::$sender->port = $port;
        if (!self::$sender->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))
                throw new Exception('Can\'t open socket.');
        if (!$group)
            self::$sender->group = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'Unknown';
        else
            self::$sender->group = $group;
        self::$sender->thread = uniqid();
    }

    public static function begin($timer) {
        self::$sender->Begin($timer);
    }

    public static function end($timer, $info = '') {
        self::$sender->End($timer, $info);
    }

    public function __destruct() {
        unset(self::$sender);
    }

}

class PRFLRSender {

    private $timers;
    private $socket;
    public $delayedSend = false;
    public $group;
    public $thread;
    public $server;
    public $port;

    public function __construct() {
        
    }

    public function __destruct() {
        socket_close($this->socket);
    }

    public function Begin($timer) {
        $this->timers[$timer] = microtime();
    }

    public function End($timer, $info = '') {

        if (!isset($this->timers[$timer]))
            return false;

        $delay = microtime() - $this->timers[$timer];

        $this->send($timer, $delay, $info);

        unset($this->timers[$timer]);
    }

    private function send($timer, $duration, $info = '') {

        // format the message
        $message = join(array($this->thread, $this->group, $timer, $duration, $info), '|');

        if ($this->socket) {
            socket_sendto($this->socket, $message, strlen($message), 0, $this->server, $this->port);
        } else {
            throw new Exception("Socket not exist\n");
        }
    }

}