<?php

namespace debugChannel;

/**
 * PHP client for debugchannel
 */
class DebugChannel
{

    /**
     * default value for senderName when sending chat messages.
     * @const ANON_IDENtIFIER
     */
    const ANON_IDENTIFIER = 'PHP-client';
    const DESCRIPTIVE_IDENTIFIER = '__DESCRIPTIVE__';
    const NO_IDENTIFIER = '__NONE__';

    /**#@+
     * @access private
     */

    /**
     * Hostname of debug channel server
     *
     * eg. eg,  192.168.2.17, 127.0.0.1, localhost
     * Domain names or ip addresses can be used.
     *
     * @var string
     */
    private $host;

    /**
     * @var string Non empty string of the channel you wish to post to
     */
    private $channel;

    /**
     * @var string Apikey to use with a debug channel account. Optional.
     */
    private $apiKey;

    /**
     * See, Allowed options include the phpRef ones below
     */
    private $options = array(
        "showPrivateMembers" => true,
        "expLvl" => 1,
        "maxDepth" => 3
    );

    /**
     * List of the options that'll be passed to phpRef
     * @var array
     */
    private $phpRefOptionsAllowed = array('expLvl', 'maxDepth', 'showIteratorContents', 'showMethods', 'showPrivateMembers', 'showStringMatches');

    /**
     * Private static process identifier
     * @var string
     */
    private static $pid;

    /**
     * Private static machine identifier
     */
    private static $machineId;

    /**
     * Monotonically increasing seqence number for message
     * @var int
     */
    private static $messageSequenceNo;

    /**#@-*/

    /**#@+
     * @access public
     */

    /**
     * Create a DebugChannel object bound to a specific channel and server.
     *
     * options can be provided which customize how explore works.
     * the options available are:
     * <table>
     * <thead><tr>
     * <th align="left">Option</th>
     * <th align="left">Default</th>
     * <th align="left">Description</th>
     * </tr></thead>
     * <tbody>
     * <tr>
     * <td align="left"><code>'expLvl'</code></td>
     * <td align="left"><code>1</code></td>
     * <td align="left">Initially expanded levels (for HTML mode only). A negative value will expand all levels</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'maxDepth'</code></td>
     * <td align="left"><code>6</code></td>
     * <td align="left">Maximum depth (<code>0</code> to disable); note that disabling it or setting a high value can produce a 100+ MB page when input involves large data</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'showIteratorContents'</code></td>
     * <td align="left"><code>FALSE</code></td>
     * <td align="left">Display iterator data (keys and values)</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'showResourceInfo'</code></td>
     * <td align="left"><code>TRUE</code></td>
     * <td align="left">Display additional information about resources</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'showMethods'</code></td>
     * <td align="left"><code>TRUE</code></td>
     * <td align="left">Display methods and parameter information on objects</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'showPrivateMembers'</code></td>
     * <td align="left"><code>FALSE</code></td>
     * <td align="left">Include private properties and methods</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'showStringMatches'</code></td>
     * <td align="left"><code>TRUE</code></td>
     * <td align="left">Perform and display string matches for dates, files, json strings, serialized data, regex patterns etc. (SLOW)</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'formatters'</code></td>
     * <td align="left"><code>array()</code></td>
     * <td align="left">Custom/external formatters (as associative array: format =&gt; className)</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'shortcutFunc'</code></td>
     * <td align="left"><code>array('r', 'rt')</code></td>
     * <td align="left">Shortcut functions used to detect the input expression. If they are namespaced, the namespace must be present as well (methods are not  supported)</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'stylePath'</code></td>
     * <td align="left"><code>'{:dir}/ref.css'</code></td>
     * <td align="left">Local path to a custom stylesheet (HTML only); <code>FALSE</code> means that no CSS is included.</td>
     * </tr>
     * <tr>
     * <td align="left"><code>'scriptPath'</code></td>
     * <td align="left"><code>'{:dir}/ref.js'</code></td>
     * <td align="left">Local path to a custom javascript (HTML only); <code>FALSE</code> means no javascript (tooltips / toggle / kbd shortcuts require JS)</td>
     * </tr>
     * </tbody>
     * </table>
     *
     * @param string $host  the string is the address of debug channel server
     * @param string $channel  the channel to publish all messages on
     * @param string $apiKey the apiKey of the user who is publishing the messages. default is null.
     * @param array $options  options array to configure the way explore traverses the object graph and renders it.
     *
     */
    public function __construct( $host, $channel, $apiKey = null, array $options = array() )
    {
        $this->host = (string) $host;
        $this->setChannel($channel);
        if( null !== $apiKey and !is_string($apiKey) ) {
            throw new \InvalidArgumentException("apiKey must be a string.");
        }
        $this->apiKey = $apiKey;
        $this->setOptions($options);
    }

    /**
     * provides getter methods for all properties private and public
     *
     * all properties will get a a getter method.
     * for exmaple the private property $name of type string
     * will get a getter method with the signature:
     * <pre><code>public function name() :: string</code></pre>
     *
     * @param string $property  The string which represents the name of the property to return.
     * @return mixed  Value of property.
     * @deprecated Use the explicit getter methods.
     * @throws \InvalidArgumentException when no property exists with the name.
     */
    public function __get( $property )
    {
        if( property_exists( $this, $property ) ) {
            return $this->$property;
        }
        throw new \InvalidArgumentException("Unknown property `{$property}`.");
    }

    /**
     * Set the channel you with to subscribe to
     *
     * the channel is the in form \w+[/\w+]* for example:
     * <ul>
     *   <li>hello/world</li>
     *   <li>logs</li>
     *   <li>project/team/chat</li>
     * </ul>
     *
     * @param string $channel  Channel to use
     * @return debugChannel\DebugChannel
     */
    public function setChannel( $channel )
    {
        $this->channel = ltrim( (string) $channel, '/' );
        return $this;
    }

    /**
     * set phpref options that will be used by this instance of D
     *
     * @param array $options  the associtivate array of options, available options specified in constructors documentation
     * @return debugChannel\DebugChannel
     */
    public function setOptions( array $options )
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * gets the options set
     *
     * @return array   associative array of options mapping option name to option value
     */
    protected function getPhpRefOptions()
    {
        $phpRefOptions = array_intersect_key(
            $this->options,
            array_flip( $this->phpRefOptionsAllowed )
        );
        $phpRefOptions['stylePath'] = false;
        $phpRefOptions['scriptPath'] = false;
        return $phpRefOptions;
    }

    /**
     * Get the debug server url
     *
     * Contains both the host and channel.
     *
     * @return string   the string is the url where the debugger can be accessed from
     */
    public function getRequestUrl()
    {
        return "http://{$this->host}:1025/{$this->channel}";
    }

    /**
     * Alias for ->explore().
     * @see debugchannel\DebugChannel
     */
    public function __invoke( $dataToLog, array $tags = array() )
    {
        return call_user_func(
            array( $this, 'log'),
            func_get_args()
        );
    }

    /**
     * Alias for ->explore().
     * @see debugchannel\DebugChannel
     */
    public function log( $dataToLog, array $tags = array() )
    {
        return call_user_func(
            array( $this, 'log'),
            func_get_args()
        );
    }

    /**
     * publishes an interactable object graph
     *
     * if val is an object or array it will generate an object graph.
     * if val is a primitive such as int, string, etc then it just displays the value.
     * It can detect recursion, replacing the reference with a "RECURSION" string.
     * $val is not modified.
     * @param mixed $val  the mixed value to publish
     * @return DebugChannel  the DebugChannel object bound to $this
     */
    public function explore( $dataToLog, array $tags = array() )
    {
        $originalRefOptions = $this->setRefConfig($this->getPhpRefOptions());

        // use the custom formatter which doesn't have the "multiple levels of nesting break out of their container' bug
        $ref = new Ref(new RHtmlSpanFormatter());

        ob_start();
        $ref->query( $dataToLog, null );
        $html = ob_get_clean();

        $this->makeRequest(
            array(
                'handler' => 'php-ref',
                'args' => array(
                    $html,
                ),
                'tags' => $tags,
            )
        );

        $this->setRefConfig($originalRefOptions);
        return $this;
    }

    /**
     * publishes the 2-dimensional array as a table
     *
     * given a 2-dimensional array, treat it as a table when rendering it.
     * In the browser it will be shown as a table, with the first dimension being
     * the rows, and the second dimension being columns.
     * The values of each cell should be primtives, ie string, int, etc but can be objects.
     * the exact method of displaying the objects is undefined hence it is advised that the
     * cells are primtives.
     *
     * @param array $table  a 2-dimensional array of values, where dimension 1 is rows, dimension 2 is columns
     * @return DebugChannel  the DebugChannel instance bound to $this
     */
    public function table(array $table)
    {
        //TODO - check this is two dimensional
        return $this->sendDebug('table', array($table));
    }

    /**
     * publishes a raw string as is
     *
     * the string is publishes as a plain string without formatting.
     * it cannot be null, and cannot be any other primtive such as int.
     *
     * @param string $text  the string to publish as raw text
     * @return DebugChannel the DebugChannel instance bound to $this.
     */
    public function string($text)
    {
        return $this->sendDebug('string', $text);
    }

    /**
     * publishes a string with syntax highlighting for the given language.
     *
     * the string is treaded as code and highlighed and formatted for that given language.
     * the complete list of languages that are supported are available <a href="https://github.com/isagalaev/highlight.js/tree/master/src/languages">here</a>.
     * this list includes:
     * <ul>
     *   <ui>bash</ui>
     *   <ui>cpp(c++)</ui>
     *   <ui>cs(c#)</ui>
     *   <ui>java</ui>
     *   <ui>javascript<ui>
     *   <ui>python</ui>
     *   <ui>php</ui>
     *   <ui>sql</ui>
     *   <ui>xml</ui>
     *   <ui>json</ui>
     * </ul>
     *
     * @param string $text  the string which contains the code to syntax highlight
     * @param string $lang  the string that represents the language the $text is in.
     * some languages will have a slight varient on what its called, ie c++ is cpp.
     * Default sql.
     * @param bool $deIndent  bool is true when you want the identation in the text to be ignored, false otherwise
     * @return DebugChannel  the DebugChannel instance bound to $this.
     */
    public function code( $text, $lang = 'sql', $deIndent = true )
    {
        if( $deIndent ) {
            $text = $this->deIndent($text);
        }
        $trace = $this->formatTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        return $this->sendDebug('syntaxHighlight', array($text, $lang, $trace));
    }

    /**
     * publishes an image to the browser.
     *
     * encodes an image in using base64 encoding to be rendered as an image resized to fit the debug box.
     * the image can be specified by its location in the filesystem or as a base64 encoded string.
     * the following file formats are allowed: jpg, bmp, and png.
     *
     * @param string $identifier  the string can be the location of the image in the filesystem either fully qualified or relative.
     * the string can also contain the image in base64 format.
     * @return DebugChannel  the DebugChannel instance bound to $this.
     */
    public function image($identifier)
    {
        assert(is_string($identifier));
        $base64 = file_exists($identifier) ? base64_encode(file_get_contents($identifier)) : $identifier;
        return $this->sendDebug('image', $base64);
    }


    /**
     * publishes a messages like a chat message in an IM client.
     *
     *
     * publishes the message text with a senders name attached.
     * the senderName can be anything, and  does not need to be the same on every consecutive call.
     *
     * @param string $message  the string containing the message to publish as IM message
     * @param string $senderName  the name of the sender that will be displayed next to the message. Default 'PHP-client'.
     * @return DebugChannel  the DebugChannel instance bound to $this.
     */
    public function chat($message, $senderName=null)
    {
        $senderName = $senderName ? $senderName : self::ANON_IDENTIFIER;

        return $this->sendDebug('chat', [$senderName, $message]);
    }

    /**
     * removes all debugs in the channel for all users
     *
     * can be called at any point, event if there are no debugs in the channel.
     * if multiple clients are publishing to the same channel, this will remove their debugs as well.
     * if multiple people are viewing the channel in browser then every user will be effected.
     *
     * @return DebugChannel  the DebugChannel instance bound to $this.
     */
    public function clear()
    {
        return $this->sendDebug('clear');
    }

    /**#@-*/

    protected function sendDebug ($handler, $args = array(), $stacktrace = array())
    {
        $this->makeRequest(
            array(
                'handler' => $handler,
                'args' => is_array($args) ? $args : array($args),
                'stacktrace' => $stacktrace
            )
        );
        return $this;
    }

    protected function filloutRequest( array $data )
    {

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $offset = 0;
        // this loop construct starts on the second element
        while( $working = next($trace) and isset($working['class']) and $working['class'] === __CLASS__ ) {
            $offset++;
        }
        // exclude all but the first call to __CLASS__, renumber array
        $data['trace'] = $this->formatTrace( array_slice($trace, $offset) );
        // tags are a required field
        $data['tags'] = isset($data['tags']) ? $data['tags'] : array();

        // add apiKey to request if set
        if( null !== $this->apiKey ) {
            $data['apiKey'] = (string) $this->apiKey;
        }

        // process id
        $data['info'] = $this->getInfoArray();

        return $data;

    }

    protected function makeRequest( $data )
    {

        $data = $this->filloutRequest($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url = $this->getRequestUrl() );
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json') );
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data) );

        $response = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);

        // have any problems
        if( $response === false ) {
            throw new \Exception("Unable to connect to debugger as `{$url}`");
        } elseif ( $curlInfo['http_code'] !== 200 ) {
            throw new \Exception($response);
        }

        return $this;

    }

    /**
     * Get client identifier
     * @return bool|string
     */
    protected function getIdentifier()
    {
        switch( $options['identifier'] ) {
            case self::ANON_IDENTIFIER:
                return 'anon';
            case self::DESCRIPTIVE_IDENTIFIER:
                return 'descriptive';
            case self::NO_IDENTIFIER;
                return false;
        }
        return $options['identifier'];
    }

    protected function setRefConfig( array $options )
    {
        $output = array();
        foreach( $options as $option => $value ) {
            $output[$option] = ref::config($option);
            ref::config($option, $value);
        }
        return $output;
    }

    protected function formatTrace( $trace )
    {
        return array_map(
            function ( $component ) {
                if( isset($component['file'], $component['line']) and $component['line'] > 0 ) {
                    $location = sprintf( "%s(%s): ", $component['file'], $component['line'] );
                } else {
                    $location = '';
                }

                $fn = isset( $component['class'] ) ? "{$component['class']}{$component['type']}" : '';
                $fn .= "{$component['function']}()";

                return array(
                    'location' => $location,
                    'fn' => $fn
                );
            },
            $trace
         );
    }

    protected function deIndent( $text )
    {
        $leadingWhitespace = array();
        $text = explode("\n", $text);
        foreach( $text as $line ) {
            if( !empty( $line ) ) {
                $leadingWhitespace[] = strlen( $line ) - strlen( ltrim( $line ) );
            }
        }
        $indent = min( $leadingWhitespace );
        foreach( $text as &$line ) {
            $line = substr( $line, $indent );
        }
        return implode("\n", $text);
    }

    protected function getInfoArray()
    {
        return array(
            'machineId' => $this->getMachineId(),
            'pid' => $this->getPid(),
            'sequenceNo' => ++self::$messageSequenceNo,
            'generationTime' => microtime(true),
        );
    }

    protected function getPid()
    {
        // process information
        if( !isset(self::$pid) ) {
            // whatever this can change
            self::$pid = md5( microtime(). getmypid() );
        }
        return self::$pid;
    }

    protected function getMachineId()
    {
        if( !isset(self::$machineId) ) {
            self::$machineId = php_uname('n');
        }
        return self::$machineId;
    }

}

// backwards compatible D
// now with added deprecated message
class D extends DebugChannel
{
    private function filloutRequest( array $data )
    {
        $data = parent::filloutRequest($data);
        $data['deprecated'] = true;
        return $data;
    }
}

#######################

/**
 * REF is a nicer alternative to PHP's print_r() / var_dump().
 *
 * @version  1.0
 * @author   digitalnature - http://digitalnature.eu
 */
class Ref {

  const MARKER_KEY = '_phpRefArrayMarker_';

  protected static

    /**
     * CPU time used for processing
     *
     * @var  array
     */
    $time   = 0,

    /**
     * Configuration (+ default values)
     *
     * @var  array
     */
    $config = array(

                // initially expanded levels (for HTML mode only)
                'expLvl'               => 0,

                // depth limit (0 = no limit);
                // this is not related to recursion
                'maxDepth'             => 6,

                // display iterator contents
                'showIteratorContents' => false,

                // display extra information about resources
                'showResourceInfo'     => true,

                // display method and parameter list on objects
                'showMethods'          => true,

                // display private properties / methods
                'showPrivateMembers'   => false,

                // peform string matches (date, file, functions, classes, json, serialized data, regex etc.)
                // note: seriously slows down queries on large amounts of data
                'showStringMatches'    => true,

                // shortcut functions used to access the query method below;
                // if they are namespaced, the namespace must be present as well (methods are not supported)
                'shortcutFunc'         => array('r', 'rt'),

                // custom/external formatters (as associative array: format => className)
                'formatters'           => array(),

                // stylesheet path (for HTML only);
                // 'false' means no styles
                'stylePath'            => '{:dir}/ref.css',

                // javascript path (for HTML only);
                // 'false' means no js
                'scriptPath'           => '{:dir}/ref.js',
              );


  protected

    /**
     * Output formatter of this instance
     *
     * @var  RFormatter
     */
    $fmt = null,

    /**
     * Some environment variables
     * used to determine feature support
     *
     * @var  string
     */
    $env = array();



  /**
   * Constructor
   *
   * @param   string|RFormatter $format      Output format ID, or formatter instance defaults to 'html'
   */
  public function __construct($format = 'html'){

    if($format instanceof RFormatter){
      $this->fmt = $format;

    }else{
      $format = isset(static::$config['formatters'][$format]) ? static::$config['formatters'][$format] : 'R' . ucfirst($format) . 'Formatter';

      if(!class_exists($format))
        throw new \Exception(sprintf('%s class not found', $format));

      $this->fmt = new $format();
    }

    $this->env = array(

      // php 5.4+ ?
      'is54'         => version_compare(PHP_VERSION, '5.4') >= 0,

      // php 5.4.6+ ?
      'is546'        => version_compare(PHP_VERSION, '5.4.6') >= 0,

      // is the 'mbstring' extension active?
      'mbStr'        => function_exists('mb_detect_encoding'),

      // @see: https://bugs.php.net/bug.php?id=52469
      'supportsDate' => (strncasecmp(PHP_OS, 'WIN', 3) !== 0) || (version_compare(PHP_VERSION, '5.3.10') >= 0),
    );
  }



  /**
   * Enforce proper use of this class
   *
   * @param   string $name
   */
  public function __get($name){
    throw new \Exception(sprintf('No such property: %s', $name));
  }



  /**
   * Enforce proper use of this class
   *
   * @param   string $name
   * @param   mixed $value
   */
  public function __set($name, $value){
    throw new \Exception(sprintf('Cannot set %s. Not allowed', $name));
  }



  /**
   * Generate structured information about a variable/value/expression (subject)
   *
   * Output is flushed to the screen
   *
   * @param   mixed $subject
   * @param   string $expression
   */
  public function query($subject, $expression = null){

    $startTime = microtime(true);

    $this->fmt->startRoot();
    $this->fmt->startExp();
    $this->evaluateExp($expression);
    $this->fmt->endExp();
    $this->evaluate($subject);
    $this->fmt->endRoot();
    $this->fmt->flush();

    static::$time += microtime(true) - $startTime;
  }

  /**
   * Executes a function the given number of times and returns the elapsed time.
   *
   * Keep in mind that the returned time includes function call overhead (including
   * microtime calls) x iteration count. This is why this is better suited for
   * determining which of two or more functions is the fastest, rather than
   * finding out how fast is a single function.
   *
   * @param   int $iterations      Number of times the function will be executed
   * @param   callable $function   Function to execute
   * @param   mixed &$output       If given, last return value will be available in this variable
   * @return  double               Elapsed time
   */
  public static function timeFunc($iterations, $function, &$output = null){

    $time = 0;

    for($i = 0; $i < $iterations; $i++){
      $start  = microtime(true);
      $output = call_user_func($function);
      $time  += microtime(true) - $start;
    }

    return round($time, 4);
  }



  /**
   * Timer utility
   *
   * First call of this function will start the timer.
   * The second call will stop the timer and return the elapsed time
   * since the timer started.
   *
   * Multiple timers can be controlled simultaneously by specifying a timer ID.
   *
   * @since   1.0
   * @param   int $id          Timer ID, optional
   * @param   int $precision   Precision of the result, optional
   * @return  void|double      Elapsed time, or void if the timer was just started
   */
  public static function timer($id = 1, $precision = 4){

    static
      $timers = array();

    // check if this timer was started, and display the elapsed time if so
    if(isset($timers[$id])){
      $elapsed = round(microtime(true) - $timers[$id], $precision);
      unset($timers[$id]);
      return $elapsed;
    }

    // ID doesn't exist, start new timer
    $timers[$id] = microtime(true);
  }



  /**
   * Parses a DocBlock comment into a data structure.
   *
   * @link    http://pear.php.net/manual/en/standards.sample.php
   * @param   string $comment    DocBlock comment (must start with /**)
   * @param   string|null $key   Field to return (optional)
   * @return  array|string|null  Array containing all fields, array/string with the contents of
   *                             the requested field, or null if the comment is empty/invalid
   */
  public static function parseComment($comment, $key = null){

    $description = '';
    $tags        = array();
    $tag         = null;
    $pointer     = '';
    $padding     = 0;
    $comment     = preg_split('/\r\n|\r|\n/', '* ' . trim($comment, "/* \t\n\r\0\x0B"));

    // analyze each line
    foreach($comment as $line){

      // drop any wrapping spaces
      $line = trim($line);

      // drop "* "
      if($line !== '')
        $line = substr($line, 2);

      if(strpos($line, '@') !== 0){

        // preserve formatting of tag descriptions,
        // because they may span across multiple lines
        if($tag !== null){
          $trimmed = trim($line);

          if($padding !== 0)
            $trimmed = static::strPad($trimmed, static::strLen($line) - $padding, ' ', STR_PAD_LEFT);
          else
            $padding = static::strLen($line) - static::strLen($trimmed);

          $pointer .= "\n{$trimmed}";
          continue;
        }

        // tag definitions have not started yet; assume this is part of the description text
        $description .= "\n{$line}";
        continue;
      }

      $padding = 0;
      $parts = explode(' ', $line, 2);

      // invalid tag? (should we include it as an empty array?)
      if(!isset($parts[1]))
        continue;

      $tag = substr($parts[0], 1);
      $line = ltrim($parts[1]);

      // tags that have a single component (eg. link, license, author, throws...);
      // note that @throws may have 2 components, however most people use it like "@throws ExceptionClass if whatever...",
      // which, if broken into two values, leads to an inconsistent description sentence
      if(!in_array($tag, array('global', 'param', 'return', 'var'))){
        $tags[$tag][] = $line;
        end($tags[$tag]);
        $pointer = &$tags[$tag][key($tags[$tag])];
        continue;
      }

      // tags with 2 or 3 components (var, param, return);
      $parts    = explode(' ', $line, 2);
      $parts[1] = isset($parts[1]) ? ltrim($parts[1]) : null;
      $lastIdx  = 1;

      // expecting 3 components on the 'param' tag: type varName varDescription
      if($tag === 'param'){
        $lastIdx = 2;
        if(in_array($parts[1][0], array('&', '$'), true)){
          $line     = ltrim(array_pop($parts));
          $parts    = array_merge($parts, explode(' ', $line, 2));
          $parts[2] = isset($parts[2]) ? ltrim($parts[2]) : null;
        }else{
          $parts[2] = $parts[1];
          $parts[1] = null;
        }
      }

      $tags[$tag][] = $parts;
      end($tags[$tag]);
      $pointer = &$tags[$tag][key($tags[$tag])][$lastIdx];
    }

    // split title from the description texts at the nearest 2x new-line combination
    // (note: loose check because 0 isn't valid as well)
    if(strpos($description, "\n\n")){
      list($title, $description) = explode("\n\n", $description, 2);

    // if we don't have 2 new lines, try to extract first sentence
    }else{
      // in order for a sentence to be considered valid,
      // the next one must start with an uppercase letter
      $sentences = preg_split('/(?<=[.?!])\s+(?=[A-Z])/', $description, 2, PREG_SPLIT_NO_EMPTY);

      // failed to detect a second sentence? then assume there's only title and no description text
      $title = isset($sentences[0]) ? $sentences[0] : $description;
      $description = isset($sentences[1]) ? $sentences[1] : '';
    }

    $title = ltrim($title);
    $description = ltrim($description);

    $data = compact('title', 'description', 'tags');

    if(!array_filter($data))
      return null;

    if($key !== null)
      return isset($data[$key]) ? $data[$key] : null;

    return $data;
  }



  /**
   * Split a regex into its components
   *
   * Based on "Regex Colorizer" by Steven Levithan (this is a translation from javascript)
   *
   * @link     https://github.com/slevithan/regex-colorizer
   * @link     https://github.com/symfony/Finder/blob/master/Expression/Regex.php#L64-74
   * @param    string $pattern
   * @return   array
   */
  public static function splitRegex($pattern){

    // detection attempt code from the Symfony Finder component
    $maybeValid = false;
    if(preg_match('/^(.{3,}?)([imsxuADU]*)$/', $pattern, $m)) {
      $start = substr($m[1], 0, 1);
      $end   = substr($m[1], -1);

      if(($start === $end && !preg_match('/[*?[:alnum:] \\\\]/', $start)) || ($start === '{' && $end === '}'))
        $maybeValid = true;
    }

    if(!$maybeValid)
      throw new \Exception('Pattern does not appear to be a valid PHP regex');

    $output              = array();
    $capturingGroupCount = 0;
    $groupStyleDepth     = 0;
    $openGroups          = array();
    $lastIsQuant         = false;
    $lastType            = 1;      // 1 = none; 2 = alternator
    $lastStyle           = null;

    preg_match_all('/\[\^?]?(?:[^\\\\\]]+|\\\\[\S\s]?)*]?|\\\\(?:0(?:[0-3][0-7]{0,2}|[4-7][0-7]?)?|[1-9][0-9]*|x[0-9A-Fa-f]{2}|u[0-9A-Fa-f]{4}|c[A-Za-z]|[\S\s]?)|\((?:\?[:=!]?)?|(?:[?*+]|\{[0-9]+(?:,[0-9]*)?\})\??|[^.?*+^${[()|\\\\]+|./', $pattern, $matches);

    $matches = $matches[0];

    $getTokenCharCode = function($token){
      if(strlen($token) > 1 && $token[0] === '\\'){
        $t1 = substr($token, 1);

        if(preg_match('/^c[A-Za-z]$/', $t1))
          return strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZ", strtoupper($t1[1])) + 1;

        if(preg_match('/^(?:x[0-9A-Fa-f]{2}|u[0-9A-Fa-f]{4})$/', $t1))
          return intval(substr($t1, 1), 16);

        if(preg_match('/^(?:[0-3][0-7]{0,2}|[4-7][0-7]?)$/', $t1))
          return intval($t1, 8);

        $len = strlen($t1);

        if($len === 1 && strpos('cuxDdSsWw', $t1) !== false)
          return null;

        if($len === 1){
          switch ($t1) {
            case 'b': return 8;
            case 'f': return 12;
            case 'n': return 10;
            case 'r': return 13;
            case 't': return 9;
            case 'v': return 11;
            default: return $t1[0];
          }
        }
      }

      return ($token !== '\\') ? $token[0] : null;
    };

    foreach($matches as $m){

      if($m[0] === '['){
        $lastCC         = null;
        $cLastRangeable = false;
        $cLastType      = 0;  // 0 = none; 1 = range hyphen; 2 = short class

        preg_match('/^(\[\^?)(]?(?:[^\\\\\]]+|\\\\[\S\s]?)*)(]?)$/', $m, $parts);

        array_shift($parts);
        list($opening, $content, $closing) = $parts;

        if(!$closing)
          throw new \Exception('Unclosed character class');

        preg_match_all('/[^\\\\-]+|-|\\\\(?:[0-3][0-7]{0,2}|[4-7][0-7]?|x[0-9A-Fa-f]{2}|u[0-9A-Fa-f]{4}|c[A-Za-z]|[\S\s]?)/', $content, $ccTokens);
        $ccTokens     = $ccTokens[0];
        $ccTokenCount = count($ccTokens);
        $output[]     = array('chr' => $opening);

        foreach($ccTokens as $i => $cm) {

          if($cm[0] === '\\'){
            if(preg_match('/^\\\\[cux]$/', $cm))
              throw new \Exception('Incomplete regex token');

            if(preg_match('/^\\\\[dsw]$/i', $cm)) {
              $output[]     = array('chr-meta' => $cm);
              $cLastRangeable  = ($cLastType !== 1);
              $cLastType       = 2;

            }elseif($cm === '\\'){
              throw new \Exception('Incomplete regex token');

            }else{
              $output[]       = array('chr-meta' => $cm);
              $cLastRangeable = $cLastType !== 1;
              $lastCC         = $getTokenCharCode($cm);
            }

          }elseif($cm === '-'){
            if($cLastRangeable){
              $nextToken = ($i + 1 < $ccTokenCount) ? $ccTokens[$i + 1] : false;

              if($nextToken){
                $nextTokenCharCode = $getTokenCharCode($nextToken[0]);

                if((!is_null($nextTokenCharCode) && $lastCC > $nextTokenCharCode) || $cLastType === 2 || preg_match('/^\\\\[dsw]$/i', $nextToken[0]))
                  throw new \Exception('Reversed or invalid range');

                $output[]       = array('chr-range' => '-');
                $cLastRangeable = false;
                $cLastType      = 1;

              }else{
                $output[] = $closing ? array('chr' => '-') : array('chr-range' => '-');
              }

            }else{
              $output[]        = array('chr' => '-');
              $cLastRangeable  = ($cLastType !== 1);
            }

          }else{
            $output[]       = array('chr' => $cm);
            $cLastRangeable = strlen($cm) > 1 || ($cLastType !== 1);
            $lastCC         = $cm[strlen($cm) - 1];
          }
        }

        $output[] = array('chr' => $closing);
        $lastIsQuant  = true;

      }elseif($m[0] === '('){
        if(strlen($m) === 2)
          throw new \Exception('Invalid or unsupported group type');

        if(strlen($m) === 1)
          $capturingGroupCount++;

        $groupStyleDepth = ($groupStyleDepth !== 5) ? $groupStyleDepth + 1 : 1;
        $openGroups[]    = $m; // opening
        $lastIsQuant     = false;
        $output[]        = array("g{$groupStyleDepth}" => $m);

      }elseif($m[0] === ')'){
        if(!count($openGroups))
          throw new \Exception('No matching opening parenthesis');

        $output[]        = array('g' . $groupStyleDepth => ')');
        $prevGroup       = $openGroups[count($openGroups) - 1];
        $prevGroup       = isset($prevGroup[2]) ? $prevGroup[2] : '';
        $lastIsQuant     = !preg_match('/^[=!]/', $prevGroup);
        $lastStyle       = "g{$groupStyleDepth}";
        $lastType        = 0;
        $groupStyleDepth = ($groupStyleDepth !== 1) ? $groupStyleDepth - 1 : 5;

        array_pop($openGroups);
        continue;

      }elseif($m[0] === '\\'){
        if(isset($m[1]) && preg_match('/^[1-9]/', $m[1])){
          $nonBackrefDigits = '';
          $num = substr(+$m, 1);

          while($num > $capturingGroupCount){
            preg_match('/[0-9]$/', $num, $digits);
            $nonBackrefDigits = $digits[0] . $nonBackrefDigits;
            $num = floor($num / 10);
          }

          if($num > 0){
            $output[] = array('meta' =>  "\\{$num}", 'text' => $nonBackrefDigits);

          }else{
            preg_match('/^\\\\([0-3][0-7]{0,2}|[4-7][0-7]?|[89])([0-9]*)/', $m, $pts);
            $output[] = array('meta' => '\\' . $pts[1], 'text' => $pts[2]);
          }

          $lastIsQuant = true;

        }elseif(isset($m[1]) && preg_match('/^[0bBcdDfnrsStuvwWx]/', $m[1])){

          if(preg_match('/^\\\\[cux]$/', $m))
            throw new \Exception('Incomplete regex token');

          $output[]    = array('meta' => $m);
          $lastIsQuant = (strpos('bB', $m[1]) === false);

        }elseif($m === '\\'){
          throw new \Exception('Incomplete regex token');

        }else{
          $output[]    = array('text' => $m);
          $lastIsQuant = true;
        }

      }elseif(preg_match('/^(?:[?*+]|\{[0-9]+(?:,[0-9]*)?\})\??$/', $m)){
        if(!$lastIsQuant)
          throw new \Exception('Quantifiers must be preceded by a token that can be repeated');

        preg_match('/^\{([0-9]+)(?:,([0-9]*))?/', $m, $interval);

        if($interval && (+$interval[1] > 65535 || (isset($interval[2]) && (+$interval[2] > 65535))))
          throw new \Exception('Interval quantifier cannot use value over 65,535');

        if($interval && isset($interval[2]) && (+$interval[1] > +$interval[2]))
          throw new \Exception('Interval quantifier range is reversed');

        $output[]     = array($lastStyle ? $lastStyle : 'meta' => $m);
        $lastIsQuant  = false;

      }elseif($m === '|'){
        if($lastType === 1 || ($lastType === 2 && !count($openGroups)))
          throw new \Exception('Empty alternative effectively truncates the regex here');

        $output[]    = count($openGroups) ? array("g{$groupStyleDepth}" => '|') : array('meta' => '|');
        $lastIsQuant = false;
        $lastType    = 2;
        $lastStyle   = '';
        continue;

      }elseif($m === '^' || $m === '$'){
        $output[]    = array('meta' => $m);
        $lastIsQuant = false;

      }elseif($m === '.'){
        $output[]    = array('meta' => '.');
        $lastIsQuant = true;

      }else{
        $output[]    = array('text' => $m);
        $lastIsQuant = true;
      }

      $lastType  = 0;
      $lastStyle = '';
    }

    if($openGroups)
      throw new \Exception('Unclosed grouping');

    return $output;
  }



  /**
   * Set or get configuration options
   *
   * @param   string $key
   * @param   mixed|null $value
   * @return  mixed
   */
  public static function config($key, $value = null){

    if(!array_key_exists($key, static::$config))
      throw new \Exception(sprintf('Unrecognized option: "%s". Valid options are: %s', $key, implode(', ', array_keys(static::$config))));

    if($value === null)
      return static::$config[$key];

    if(is_array(static::$config[$key]))
      return static::$config[$key] = (array)$value;

    return static::$config[$key] = $value;
  }



  /**
   * Total CPU time used by the class
   *
   * @param   int precision
   * @return  double
   */
  public static function getTime($precision = 4){
    return round(static::$time, $precision);
  }



  /**
   * Determines the input expression(s) passed to the shortcut function
   *
   * @param   array &$options   Optional, options to gather (from operators)
   * @return  array             Array of string expressions
   */
  public static function getInputExpressions(array &$options = null){

    // used to determine the position of the current call,
    // if more queries calls were made on the same line
    static $lineInst = array();

    // pull only basic info with php 5.3.6+ to save some memory
    $trace = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : debug_backtrace();

    while($callee = array_pop($trace)){

      // extract only the information we neeed
      $callee = array_intersect_key($callee, array_fill_keys(array('file', 'function', 'line'), false));
      extract($callee);

      // skip, if the called function doesn't match the shortcut function name
      if(!$function || !preg_grep("/{$function}/i" , static::$config['shortcutFunc']))
        continue;

      if(!$line || !$file)
        return array();

      $code     = file($file);
      $code     = $code[$line - 1]; // multiline expressions not supported!
      $instIndx = 0;
      $tokens   = token_get_all("<?php {$code}");

      // locate the caller position in the line, and isolate argument tokens
      foreach($tokens as $i => $token){

        // match token with our shortcut function name
        if(is_string($token) || ($token[0] !== T_STRING) || (strcasecmp($token[1], $function) !== 0))
          continue;

        // is this some method that happens to have the same name as the shortcut function?
        if(isset($tokens[$i - 1]) && is_array($tokens[$i - 1]) && in_array($tokens[$i - 1][0], array(T_DOUBLE_COLON, T_OBJECT_OPERATOR), true))
          continue;

        // find argument definition start, just after '('
        if(isset($tokens[$i + 1]) && ($tokens[$i + 1][0] === '(')){
          $instIndx++;

          if(!isset($lineInst[$line]))
            $lineInst[$line] = 0;

          if($instIndx <= $lineInst[$line])
            continue;

          $lineInst[$line]++;

          // gather options
          if($options !== null){
            $j = $i - 1;
            while(isset($tokens[$j]) && is_string($tokens[$j]) && in_array($tokens[$j], array('@', '+', '-', '!', '~')))
              $options[] = $tokens[$j--];
          }

          $lvl = $index = $curlies = 0;
          $expressions = array();

          // get the expressions
          foreach(array_slice($tokens, $i + 2) as $token){

            if(is_array($token)){
              if($token[0] !== T_COMMENT)
                $expressions[$index][] = ($token[0] !== T_WHITESPACE) ? $token[1] : ' ';

              continue;
            }

            if($token === '{')
              $curlies++;

            if($token === '}')
              $curlies--;

            if($token === '(')
              $lvl++;

            if($token === ')')
              $lvl--;

            // assume next argument if a comma was encountered,
            // and we're not insde a curly bracket or inner parentheses
            if(($curlies < 1) && ($lvl === 0) && ($token === ',')){
              $index++;
              continue;
            }

            // negative parentheses count means we reached the end of argument definitions
            if($lvl < 0){
              foreach($expressions as &$expression)
                $expression = trim(implode('', $expression));

              return $expressions;
            }

            $expressions[$index][] = $token;
          }

          break;
        }
      }
    }
  }



  /**
   * Get all parent classes of a class
   *
   * @param   Reflector $class   Reflection object
   * @return  array              Array of ReflectionClass objects (starts with the ancestor, ends with the given class)
   */
  protected static function getParentClasses(\Reflector $class){

    $parents = array($class);
    while(($class = $class->getParentClass()) !== false)
      $parents[] = $class;

    return array_reverse($parents);
  }



  /**
   * Generate class / function info
   *
   * @param   Reflector $reflector      Class name or reflection object
   * @param   string $single            Skip parent classes
   * @param   Reflector|null $context   Object context (for methods)
   * @return  string
   */
  protected function fromReflector(\Reflector $reflector, $single = '', \Reflector $context = null){

    // @todo: test this
    $hash = var_export(func_get_args(), true);

    if($this->fmt->didCache($hash))
      return;

    $items = array($reflector);

    if(($single === '') && ($reflector instanceof \ReflectionClass))
      $items = static::getParentClasses($reflector);

    $first = true;
    foreach($items as $item){

      if(!$first)
        $this->fmt->sep(' :: ');

      $first    = false;
      $name     = ($single !== '') ? $single : $item->getName();
      $comments = $item->isInternal() ? array() : static::parseComment($item->getDocComment());
      $meta     = array('sub' => array());
      $bubbles  = array();

      if($item->isInternal()){
        $extension = $item->getExtension();
        $meta['title'] = ($extension instanceof \ReflectionExtension) ? sprintf('Internal - part of %s (%s)', $extension->getName(), $extension->getVersion()) : 'Internal';

      }else{
        $comments = static::parseComment($item->getDocComment());

        if($comments)
          $meta += $comments;

        $meta['sub'][] = array('Defined in', basename($item->getFileName()) . ':' . $item->getStartLine());
      }

      if(($item instanceof \ReflectionFunction) || ($item instanceof \ReflectionMethod)){
        if(($context !== null) && ($context->getShortName() !== $item->getDeclaringClass()->getShortName()))
          $meta['sub'][] = array('Inherited from', $item->getDeclaringClass()->getShortName());

        if($item instanceof \ReflectionMethod){
          try{
            $proto = $item->getPrototype();
            $meta['sub'][] = array('Prototype defined by', $proto->class);
          }catch(\Exception $e){}
        }

        $this->fmt->text('name', $name, $meta, $this->linkify($item));
        continue;
      }

      // @todo: maybe - list interface methods
      if(!($item->isInterface() || ($this->env['is54'] && $item->isTrait()))){

        if($item->isAbstract())
          $bubbles[] = array('A', 'Abstract');

        if($item->isFinal())
          $bubbles[] = array('F', 'Final');

        // php 5.4+ only
        if($this->env['is54'] && $item->isCloneable())
          $bubbles[] = array('C', 'Cloneable');

        if($item->isIterateable())
          $bubbles[] = array('X', 'Iterateable');

      }

      if($item->isInterface() && $single !== '')
        $bubbles[] = array('I', 'Interface');

      if($bubbles)
        $this->fmt->bubbles($bubbles);

      if($item->isInterface() && $single === '')
        $name .= sprintf(' (%d)', count($item->getMethods()));

      $this->fmt->text('name', $name, $meta, $this->linkify($item));
    }

    $this->fmt->cacheLock($hash);
  }



  /**
   * Generates an URL that points to the documentation page relevant for the requested context
   *
   * For internal functions and classes, the URI will point to the local PHP manual
   * if installed and configured, otherwise to php.net/manual (the english one)
   *
   * @param   Reflector $reflector    Reflector object (used to determine the URL scheme for internal stuff)
   * @param   string|null $constant   Constant name, if this is a request to linkify a constant
   * @return  string|null             URL
   */
  protected function linkify(\Reflector $reflector, $constant = null){

    static $docRefRoot = null, $docRefExt = null;

    // most people don't have this set
    if(!$docRefRoot)
      $docRefRoot = ($docRefRoot = rtrim(ini_get('docref_root'), '/')) ? $docRefRoot : 'http://php.net/manual/en';

    if(!$docRefExt)
      $docRefExt = ($docRefExt = ini_get('docref_ext')) ? $docRefExt : '.php';

    $phpNetSchemes = array(
      'class'     => $docRefRoot . '/class.%s'    . $docRefExt,
      'function'  => $docRefRoot . '/function.%s' . $docRefExt,
      'method'    => $docRefRoot . '/%2$s.%1$s'   . $docRefExt,
      'property'  => $docRefRoot . '/class.%2$s'  . $docRefExt . '#%2$s.props.%1$s',
      'constant'  => $docRefRoot . '/class.%2$s'  . $docRefExt . '#%2$s.constants.%1$s',
    );

    $url  = null;
    $args = array();

    // determine scheme
    if($constant !== null){
      $type = 'constant';
      $args[] = $constant;

    }else{
      $type = explode('\\', get_class($reflector));
      $type = strtolower(ltrim(end($type), 'Reflection'));

      if($type === 'object')
        $type = 'class';
    }

    // properties don't have the internal flag;
    // also note that many internal classes use some kind of magic as properties (eg. DateTime);
    // these will only get linkifed if the declared class is internal one, and not an extension :(
    $parent = ($type !== 'property') ? $reflector : $reflector->getDeclaringClass();

    // internal function/method/class/property/constant
    if($parent->isInternal()){
      $args[] = $reflector->name;

      if(in_array($type, array('method', 'property'), true))
        $args[] = $reflector->getDeclaringClass()->getName();

      $args = array_map(function($text){
        return str_replace('_', '-', ltrim(strtolower($text), '\\_'));
      }, $args);

      // check for some special cases that have no links
      $valid = (($type === 'method') || (strcasecmp($parent->name, 'stdClass') !== 0))
            && (($type !== 'method') || (($reflector->name === '__construct') || strpos($reflector->name, '__') !== 0));

      if($valid)
        $url = vsprintf($phpNetSchemes[$type], $args);

    // custom
    }else{
      switch(true){

        // WordPress function;
        // like pretty much everything else in WordPress, API links are inconsistent as well;
        // so we're using queryposts.com as doc source for API
        case ($type === 'function') && class_exists('WP') && defined('ABSPATH') && defined('WPINC'):
          if(strpos($reflector->getFileName(), realpath(ABSPATH . WPINC)) === 0){
            $url = sprintf('http://queryposts.com/function/%s', urlencode(strtolower($reflector->getName())));
            break;
          }

        // @todo: handle more apps
      }

    }

    return $url;
  }



  /**
   * Evaluates the given variable
   *
   * @param   mixed &$subject   Variable to query
   * @param   bool $specialStr  Should this be interpreted as a special string?
   * @return  mixed             Result (both HTML and text modes generate strings)
   */
  protected function evaluate(&$subject, $specialStr = false){

    switch($type = gettype($subject)){

      // null value
      case 'NULL':
        return $this->fmt->text('null');

      // integer/double/float
      case 'integer':
      case 'double':
        return $this->fmt->text($type, $subject, $type);

      // boolean
      case 'boolean':
        $text = $subject ? 'true' : 'false';
        return $this->fmt->text($text, $text, $type);

      // arrays
      case 'array':

        // empty array?
        if(empty($subject)){
          $this->fmt->text('array');
          return $this->fmt->emptyGroup();
        }

        if(isset($subject[static::MARKER_KEY])){
          unset($subject[static::MARKER_KEY]);
          $this->fmt->text('array');
          $this->fmt->emptyGroup('recursion');
          return;
        }

        // first recursion level detection;
        // this is optional (used to print consistent recursion info)
        foreach($subject as $key => &$value){
          if(!is_array($value))
            continue;

          // save current value in a temporary variable
          $buffer = $value;

          // assign new value
          $value = ($value !== 1) ? 1 : 2;

          // if they're still equal, then we have a reference
          if($value === $subject){
            $value = $buffer;
            $value[static::MARKER_KEY] = true;
            $this->evaluate($value);
            return;
          }

          // restoring original value
          $value = $buffer;
        }

        $this->fmt->text('array');
        $count = count($subject);
        if(!$this->fmt->startGroup($count))
          return;

        $max = max(array_map('static::strLen', array_keys($subject)));
        $subject[static::MARKER_KEY] = true;

        foreach($subject as $key => &$value){

          // ignore our temporary marker
          if($key === static::MARKER_KEY)
            continue;

          $keyInfo = gettype($key);

          if($keyInfo === 'string'){
            $encoding = $this->env['mbStr'] ? mb_detect_encoding($key) : '';
            $keyLen   = $encoding && ($encoding !== 'ASCII') ? static::strLen($key) . '; ' . $encoding : static::strLen($key);
            $keyInfo  = "{$keyInfo}({$keyLen})";
          }else{
            $keyLen   = strlen($key);
          }

          $this->fmt->startRow();
          $this->fmt->text('key', $key, "Key: {$keyInfo}");
          $this->fmt->colDiv($max - $keyLen);
          $this->fmt->sep('=>');
          $this->fmt->colDiv();
          $this->evaluate($value, $specialStr);
          $this->fmt->endRow();
        }

        unset($subject[static::MARKER_KEY]);
        $this->fmt->endGroup();
        return;

      // resource
      case 'resource':
        $meta    = array();
        $resType = get_resource_type($subject);

        $this->fmt->text('resource', strval($subject));

        if(!static::$config['showResourceInfo'])
          return $this->fmt->emptyGroup($resType);

        // @see: http://php.net/manual/en/resource.php
        // need to add more...
        switch($resType){

          // curl extension resource
          case 'curl':
            $meta = curl_getinfo($subject);
          break;

          case 'FTP Buffer':
            $meta = array(
              'time_out'  => ftp_get_option($subject, FTP_TIMEOUT_SEC),
              'auto_seek' => ftp_get_option($subject, FTP_AUTOSEEK),
            );

          break;

          // gd image extension resource
          case 'gd':
            $meta = array(
               'size'       => sprintf('%d x %d', imagesx($subject), imagesy($subject)),
               'true_color' => imageistruecolor($subject),
            );

          break;

          case 'ldap link':
            $constants = get_defined_constants();

            array_walk($constants, function($value, $key) use(&$constants){
              if(strpos($key, 'LDAP_OPT_') !== 0)
                unset($constants[$key]);
            });

            // this seems to fail on my setup :(
            unset($constants['LDAP_OPT_NETWORK_TIMEOUT']);

            foreach(array_slice($constants, 3) as $key => $value)
              if(ldap_get_option($subject, (int)$value, $ret))
                $meta[strtolower(substr($key, 9))] = $ret;

          break;

          // mysql connection (mysql extension is deprecated from php 5.4/5.5)
          case 'mysql link':
          case 'mysql link persistent':
            $dbs = array();
            $query = @mysql_list_dbs($subject);
            while($row = @mysql_fetch_array($query))
              $dbs[] = $row['Database'];

            $meta = array(
              'host'             => ltrim(@mysql_get_host_info ($subject), 'MySQL host info: '),
              'server_version'   => @mysql_get_server_info($subject),
              'protocol_version' => @mysql_get_proto_info($subject),
              'databases'        => $dbs,
            );

          break;

          // mysql result
          case 'mysql result':
            while($row = @mysql_fetch_object($subject))
              $meta[] = (array)$row;

          break;

          // stream resource (fopen, fsockopen, popen, opendir etc)
          case 'stream':
            $meta = stream_get_meta_data($subject);
          break;

        }

        if(!$meta)
          return $this->fmt->emptyGroup($resType);


        if(!$this->fmt->startGroup($resType))
          return;

        $max = max(array_map('static::strLen', array_keys($meta)));
        foreach($meta as $key => $value){
          $this->fmt->startRow();
          $this->fmt->text('resourceProp', ucwords(str_replace('_', ' ', $key)));
          $this->fmt->colDiv($max - static::strLen($key));
          $this->fmt->sep(':');
          $this->fmt->colDiv();
          $this->evaluate($value);
          $this->fmt->endRow();
        }
        $this->fmt->endGroup();
        return;

      // string
      case 'string':

        $length   = static::strLen($subject);
        $encoding = $this->env['mbStr'] ? mb_detect_encoding($subject) : false;
        $info     = $encoding && ($encoding !== 'ASCII') ? $length . '; ' . $encoding : $length;

        if($specialStr){
          $this->fmt->sep('"');
          $this->fmt->text(array('string', 'special'), $subject, "string({$info})");
          $this->fmt->sep('"');
          return;
        }

        $this->fmt->text('string', $subject, "string({$info})");

        // advanced checks only if there are 3 characteres or more
        if(static::$config['showStringMatches'] && ($length > 2) && (trim($subject) !== '')){

          $isNumeric = is_numeric($subject);

          // very simple check to determine if the string could match a file path
          // @note: this part of the code is very expensive
          $isFile = ($length < 2048)
            && (max(array_map('strlen', explode('/', str_replace('\\', '/', $subject)))) < 128)
            && !preg_match('/[^\w\.\-\/\\\\:]|\..*\.|\.$|:(?!(?<=^[a-zA-Z]:)[\/\\\\])/', $subject);

          if($isFile){
            try{
              $file  = new \SplFileInfo($subject);
              $flags = array();
              $perms = $file->getPerms();

              if(($perms & 0xC000) === 0xC000)       // socket
                $flags[] = 's';
              elseif(($perms & 0xA000) === 0xA000)   // symlink
                $flags[] = 'l';
              elseif(($perms & 0x8000) === 0x8000)   // regular
                $flags[] = '-';
              elseif(($perms & 0x6000) === 0x6000)   // block special
                $flags[] = 'b';
              elseif(($perms & 0x4000) === 0x4000)   // directory
                $flags[] = 'd';
              elseif(($perms & 0x2000) === 0x2000)   // character special
                $flags[] = 'c';
              elseif(($perms & 0x1000) === 0x1000)   // FIFO pipe
                $flags[] = 'p';
              else                                   // unknown
                $flags[] = 'u';

              // owner
              $flags[] = (($perms & 0x0100) ? 'r' : '-');
              $flags[] = (($perms & 0x0080) ? 'w' : '-');
              $flags[] = (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));

              // group
              $flags[] = (($perms & 0x0020) ? 'r' : '-');
              $flags[] = (($perms & 0x0010) ? 'w' : '-');
              $flags[] = (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));

              // world
              $flags[] = (($perms & 0x0004) ? 'r' : '-');
              $flags[] = (($perms & 0x0002) ? 'w' : '-');
              $flags[] = (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

              $size = is_dir($subject) ? '' : sprintf(' %.2fK', $file->getSize() / 1024);

              $this->fmt->startContain('file', true);
              $this->fmt->text('file', implode('', $flags) . $size);
              $this->fmt->endContain();

            }catch(\Exception $e){
              $isFile = false;
            }
          }

          // class/interface/function
          if(!preg_match('/[^\w+\\\\]/', $subject) && ($length < 96)){
            $isClass = class_exists($subject, false);
            if($isClass){
              $this->fmt->startContain('class', true);
              $this->fromReflector(new \ReflectionClass($subject));
              $this->fmt->endContain();
            }

            if(!$isClass && interface_exists($subject, false)){
              $this->fmt->startContain('interface', true);
              $this->fromReflector(new \ReflectionClass($subject));
              $this->fmt->endContain('interface');
            }

            if(function_exists($subject)){
              $this->fmt->startContain('function', true);
              $this->fromReflector(new \ReflectionFunction($subject));
              $this->fmt->endContain('function');
            }
          }


          // skip serialization/json/date checks if the string appears to be numeric,
          // or if it's shorter than 5 characters
          if(!$isNumeric && ($length > 4)){
            if(($length < 128) && $this->env['supportsDate'] && !preg_match('/[^A-Za-z0-9.:+\s\-\/]/', $subject)){
              try{
                $date   = new \DateTime($subject);
                $errors = \DateTime::getLastErrors();

                if(($errors['warning_count'] < 1) && ($errors['error_count'] < 1)){
                  $now    = new \Datetime('now');
                  $nowUtc = new \Datetime('now', new \DateTimeZone('UTC'));
                  $diff   = $now->diff($date);

                  $map = array(
                    'y' => 'yr',
                    'm' => 'mo',
                    'd' => 'da',
                    'h' => 'hr',
                    'i' => 'min',
                    's' => 'sec',
                  );

                  $timeAgo = 'now';
                  foreach($map as $k => $label){
                    if($diff->{$k} > 0){
                      $timeAgo = $diff->format("%R%{$k}{$label}");
                      break;
                    }
                  }

                  $tz   = $date->getTimezone();
                  $offs = round($tz->getOffset($nowUtc) / 3600);

                  if($offs > 0)
                    $offs = "+{$offs}";

                  $timeAgo .= ((int)$offs !== 0) ? ' ' . sprintf('%s (UTC%s)', $tz->getName(), $offs) : ' UTC';
                  $this->fmt->startContain('date', true);
                  $this->fmt->text('date', $timeAgo);
                  $this->fmt->endContain();

                }
              }catch(\Exception $e){
                // not a date
              }

            }

            // attempt to detect if this is a serialized string
            static $unserializing = 0;
            $isSerialized = ($unserializing < 3)
              && (($subject[$length - 1] === ';') || ($subject[$length - 1] === '}'))
              && in_array($subject[0], array('s', 'a', 'O'), true)
              && ((($subject[0] === 's') && ($subject[$length - 2] !== '"')) || preg_match("/^{$subject[0]}:[0-9]+:/s", $subject))
              && (($unserialized = @unserialize($subject)) !== false);

            if($isSerialized){
              $unserializing++;
              $this->fmt->startContain('serialized', true);
              $this->evaluate($unserialized);
              $this->fmt->endContain();
              $unserializing--;
            }

            // try to find out if it's a json-encoded string;
            // only do this for json-encoded arrays or objects, because other types have too generic formats
            static $decodingJson = 0;
            $isJson = !$isSerialized && ($decodingJson < 3) && in_array($subject[0], array('{', '['), true);

            if($isJson){
              $decodingJson++;
              $json = json_decode($subject);

              if($isJson = (json_last_error() === JSON_ERROR_NONE)){
                $this->fmt->startContain('json', true);
                $this->evaluate($json);
                $this->fmt->endContain();
              }

              $decodingJson--;
            }

            // attempt to match a regex
            if($length < 768){
              try{
                $components = $this->splitRegex($subject);
                if($components){
                  $regex = '';

                  $this->fmt->startContain('regex', true);
                  foreach($components as $component)
                    $this->fmt->text('regex-' . key($component), reset($component));
                  $this->fmt->endContain();
                }

              }catch(\Exception $e){
                // not a regex
              }

            }
          }
        }

        return;
    }

    // if we reached this point, $subject must be an object

    // track objects to detect recursion
    static $hashes = array();

    // hash ID of this object
    $hash = spl_object_hash($subject);
    $recursion = isset($hashes[$hash]);

    // sometimes incomplete objects may be created from string unserialization,
    // if the class to which the object belongs wasn't included until the unserialization stage...
    if($subject instanceof \__PHP_Incomplete_Class){
      $this->fmt->text('object');
      $this->fmt->emptyGroup('incomplete');
      return;
    }

    // check cache at this point
    if(!$recursion && $this->fmt->didCache($hash))
      return;

    $reflector = new \ReflectionObject($subject);
    $this->fmt->startContain('class');
    $this->fromReflector($reflector);
    $this->fmt->text('object', ' object');
    $this->fmt->endContain();

    // already been here?
    if($recursion)
      return $this->fmt->emptyGroup('recursion');

    $hashes[$hash] = 1;

    $flags = \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED;

    if(static::$config['showPrivateMembers'])
      $flags |= \ReflectionProperty::IS_PRIVATE;

    $props   = $reflector->getProperties($flags);
    $methods = array();

    if(static::$config['showMethods']){
      $flags = \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED;

      if(static::$config['showPrivateMembers'])
        $flags |= \ReflectionMethod::IS_PRIVATE;

      $methods = $reflector->getMethods($flags);
    }

    $constants  = $reflector->getConstants();
    $interfaces = $reflector->getInterfaces();
    $traits     = $this->env['is54'] ? $reflector->getTraits() : array();
    $parents    = static::getParentClasses($reflector);

    // work-around for https://bugs.php.net/bug.php?id=49154
    // @see http://stackoverflow.com/questions/15672287/strange-behavior-of-reflectiongetproperties-with-numeric-keys
    if(!$this->env['is54']){
      $props = array_values(array_filter($props, function($prop) use($subject){
        return !$prop->isPublic() || property_exists($subject, $prop->name);
      }));
    }

    // no data to display?
    if(!$props && !$methods && !$constants && !$interfaces && !$traits){
      unset($hashes[$hash]);
      return $this->fmt->emptyGroup();
    }

    if(!$this->fmt->startGroup())
      return;

    // show contents for iterators
    if(static::$config['showIteratorContents'] && $reflector->isIterateable()){

      $itContents = iterator_to_array($subject);
      $this->fmt->sectionTitle(sprintf('Contents (%d)', count($itContents)));

      foreach($itContents as $key => $value){
        $keyInfo = gettype($key);
        if($keyInfo === 'string'){
          $encoding = $this->env['mbStr'] ? mb_detect_encoding($key) : '';
          $length   = $encoding && ($encoding !== 'ASCII') ? static::strLen($key) . '; ' . $encoding : static::strLen($key);
          $keyInfo  = sprintf('%s(%s)', $keyInfo, $length);
        }

        $this->fmt->startRow();
        $this->fmt->text(array('key', 'iterator'), $key, sprintf('Iterator key: %s', $keyInfo));
        $this->fmt->colDiv();
        $this->fmt->sep('=>');
        $this->fmt->colDiv();
        $this->evaluate($value);
        $this->fmt->endRow();
      }
    }

    // display the interfaces this objects' class implements
    if($interfaces){
      $items = array();
      $this->fmt->sectionTitle('Implements');
      $this->fmt->startRow();
      $this->fmt->startContain('interfaces');

      $i     = 0;
      $count = count($interfaces);

      foreach($interfaces as $name => $interface){
        $this->fromReflector($interface);

        if(++$i < $count)
          $this->fmt->sep(', ');
      }

      $this->fmt->endContain();
      $this->fmt->endRow();
    }

    // traits this objects' class uses
    if($traits){
      $items = array();
      $this->fmt->sectionTitle('Uses');
      $this->fmt->startRow();
      $this->fmt->startContain('traits');

      $i     = 0;
      $count = count($traits);

      foreach($traits as $name => $trait){
        $this->fromReflector($trait);

        if(++$i < $count)
          $this->fmt->sep(', ');
      }

      $this->fmt->endContain();
      $this->fmt->endRow();
    }

    // class constants
    if($constants){
      $this->fmt->sectionTitle('Constants');
      $max = max(array_map('static::strLen', array_keys($constants)));
      foreach($constants as $name => $value){
        $meta = null;
        $type = array('const');
        foreach($parents as $parent){
          if($parent->hasConstant($name)){
            if($parent !== $reflector){
              $type[] = 'inherited';
              $meta = array('sub' => array(array('Prototype defined by', $parent->name)));
            }
            break;
          }
        }

        $this->fmt->startRow();
        $this->fmt->sep('::');
        $this->fmt->colDiv();
        $this->fmt->startContain($type);
        $this->fmt->text('name', $name, $meta, $this->linkify($parent, $name));
        $this->fmt->endContain();
        $this->fmt->colDiv($max - static::strLen($name));
        $this->fmt->sep('=');
        $this->fmt->colDiv();
        $this->evaluate($value);
        $this->fmt->endRow();
      }
    }

    // object/class properties
    if($props){
      $this->fmt->sectionTitle('Properties');

      $max = 0;
      foreach($props as $idx => $prop)
        if(($propNameLen = static::strLen($prop->name)) > $max)
          $max = $propNameLen;

      foreach($props as $idx => $prop){

        $bubbles     = array();
        $sourceClass = $prop->getDeclaringClass();
        $inherited   = $reflector->getShortName() !== $sourceClass->getShortName();
        $meta        = $sourceClass->isInternal() ? null : static::parseComment($prop->getDocComment());

        if($meta){
          if($inherited)
            $meta['sub'] = array(array('Declared in', $sourceClass->getShortName()));

          if(isset($meta['tags']['var'][0]))
            $meta['left'] = $meta['tags']['var'][0][0];

          unset($meta['tags']);
        }

        if($prop->isProtected() || $prop->isPrivate())
          $prop->setAccessible(true);

        $value = $prop->getValue($subject);

        $this->fmt->startRow();
        $this->fmt->sep($prop->isStatic() ? '::' : '->');
        $this->fmt->colDiv();

        $bubbles  = array();
        if($prop->isProtected())
          $bubbles[] = array('P', 'Protected');

        if($prop->isPrivate())
          $bubbles[] = array('!', 'Private');

        $this->fmt->bubbles($bubbles);

        $type = array('prop');

        if($inherited)
          $type[] = 'inherited';

        if($prop->isPrivate())
          $type[] = 'private';

        $this->fmt->colDiv(2 - count($bubbles));
        $this->fmt->startContain($type);
        $this->fmt->text('name', $prop->name, $meta, $this->linkify($prop));
        $this->fmt->endContain();
        $this->fmt->colDiv($max - static::strLen($prop->name));
        $this->fmt->sep('=');
        $this->fmt->colDiv();
        $this->evaluate($value);
        $this->fmt->endRow();
      }
    }

    // class methods
    if($methods){

      $this->fmt->sectionTitle('Methods');
      foreach($methods as $idx => $method){
        $this->fmt->startRow();
        $this->fmt->sep($method->isStatic() ? '::' : '->');
        $this->fmt->colDiv();

        $bubbles = array();
        if($method->isAbstract())
          $bubbles[] = array('A', 'Abstract');

        if($method->isFinal())
          $bubbles[] = array('F', 'Final');

        if($method->isProtected())
          $bubbles[] = array('P', 'Protected');

        if($method->isPrivate())
          $bubbles[] = array('!', 'Private');

        $this->fmt->bubbles($bubbles);

        $this->fmt->colDiv(4 - count($bubbles));

        // is this method inherited?
        $inherited = $reflector->getShortName() !== $method->getDeclaringClass()->getShortName();

        $type = array('method');

        if($inherited)
          $type[] = 'inherited';

        if($method->isPrivate())
          $type[] = 'private';

        $this->fmt->startContain($type);

        $name = $method->name;
        if($method->returnsReference())
          $name = "&{$name}";

        $this->fromReflector($method, $name, $reflector);

        $paramCom   = $method->isInternal() ? array() : static::parseComment($method->getDocComment(), 'tags');
        $paramCom   = empty($paramCom['param']) ? array() : $paramCom['param'];
        $paramCount = $method->getNumberOfParameters();

        $this->fmt->sep('(');

        // process arguments
        foreach($method->getParameters() as $idx => $parameter){
          $meta      = null;
          $paramName = "\${$parameter->name}";
          $optional  = $parameter->isOptional();

          if($parameter->isPassedByReference())
            $paramName = "&{$paramName}";

          $type = array('param');

          if($optional)
            $type[] = 'optional';

          $this->fmt->startContain($type);

          // attempt to build meta
          foreach($paramCom as $tag){
            list($pcTypes, $pcName, $pcDescription) = $tag;
            if($pcName !== $paramName)
              continue;

            $meta = array('title' => $pcDescription);

            if($pcTypes)
              $meta['left'] = $pcTypes;

            break;
          }

          try{
            $paramClass = $parameter->getClass();
          }catch(\Exception $e){
            // @see https://bugs.php.net/bug.php?id=32177&edit=1
          }

          if($paramClass){
            $this->fmt->startContain('hint');
            $this->fromReflector($paramClass, $paramClass->name);
            $this->fmt->endContain();
            $this->fmt->sep(' ');
          }

          if($parameter->isArray()){
            $this->fmt->text('hint', 'array');
            $this->fmt->sep(' ');
          }

          $this->fmt->text('name', $paramName, $meta);

          if($optional){
            $paramValue = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            $this->fmt->sep(' = ');

            if($this->env['is546'] && !$parameter->getDeclaringFunction()->isInternal() && $parameter->isDefaultValueConstant()){
              $this->fmt->text('constant', $parameter->getDefaultValueConstantName(), 'Constant');

            }else{
              $this->evaluate($paramValue, true);
            }
          }

          $this->fmt->endContain();

          if($idx < $paramCount - 1)
            $this->fmt->sep(', ');
        }
        $this->fmt->sep(')');
        $this->fmt->endContain();
        $this->fmt->endRow();
      }
    }

    unset($hashes[$hash]);
    $this->fmt->endGroup();

    $this->fmt->cacheLock($hash);
  }



  /**
   * Scans for known classes and functions inside the provided expression,
   * and linkifies them when possible
   *
   * @param   string $expression   Expression to format
   * @return  string               Formatted output
   */
  protected function evaluateExp($expression = null){

    if($expression === null)
      return;

    if(static::strLen($expression) > 120)
      $expression = substr($expression, 0, 120) . '...';

    $this->fmt->sep('> ');

    $backtrace = debug_backtrace();
    $backtrace = $backtrace[2];

    if(strpos($expression, '(') === false)
      return $this->fmt->text('expTxt', $expression, array('sub' => array(array('Called from', $backtrace['file'] . ' line ' . $backtrace['line']))));

    $keywords = array_map('trim', explode('(', $expression, 2));
    $parts = array();

    // try to find out if this is a function
    try{
      $reflector = new \ReflectionFunction($keywords[0]);
      $parts[] = array($keywords[0], $reflector, '');

    }catch(\Exception $e){

      if(stripos($keywords[0], 'new ') === 0){
        $cn = explode(' ' , $keywords[0], 2);

        // linkify 'new keyword' (as constructor)
        try{
          $reflector = new \ReflectionMethod($cn[1], '__construct');
          $parts[] = array($cn[0], $reflector, '');

        }catch(\Exception $e){
          $reflector = null;
          $parts[] = $cn[0];
        }

        // class name...
        try{
          $reflector = new \ReflectionClass($cn[1]);
          $parts[] = array($cn[1], $reflector, ' ');

        }catch(\Exception $e){
          $reflector = null;
          $parts[] = $cn[1];
        }

      }else{

        // we can only linkify methods called statically
        if(strpos($keywords[0], '::') === false)
          return $this->fmt->text('expTxt', $expression);

        $cn = explode('::', $keywords[0], 2);

        // attempt to linkify class name
        try{
          $reflector = new \ReflectionClass($cn[0]);
          $parts[] = array($cn[0], $reflector, '');

        }catch(\Exception $e){
          $reflector = null;
          $parts[] = $cn[0];
        }

        // perhaps it's a static class method; try to linkify method
        try{
          $reflector = new \ReflectionMethod($cn[0], $cn[1]);
          $parts[] = array($cn[1], $reflector, '::');

        }catch(\Exception $e){
          $reflector = null;
          $parts[] = $cn[1];
        }
      }
    }

    $parts[] = "({$keywords[1]}";

    foreach($parts as $element){
      if(!is_array($element)){
        $this->fmt->text('expTxt', $element);
        continue;
      }

      list($text, $reflector, $prefix) = $element;

      if($prefix !== '')
        $this->fmt->text('expTxt', $prefix);

      $this->fromReflector($reflector, $text);
    }

  }



  /**
   * Calculates real string length
   *
   * @param   string $string
   * @return  int
   */
  protected static function strLen($string){
    $encoding = function_exists('mb_detect_encoding') ? mb_detect_encoding($string) : false;
    return $encoding ? mb_strlen($string, $encoding) : strlen($string);
  }



  /**
   * Safe str_pad alternative
   *
   * @param   string $string
   * @param   int $padLen
   * @param   string $padStr
   * @param   int $padType
   * @return  string
   */
  protected static function strPad($input, $padLen, $padStr = ' ', $padType = STR_PAD_RIGHT){
    $diff = strlen($input) - static::strLen($input);
    return str_pad($input, $padLen + $diff, $padStr, $padType);
  }

}



/**
 * Formatter abstraction
 */
abstract class RFormatter{

  /**
   * Flush output and send contents to the output device
   */
  abstract public function flush();

  /**
   * Generate a base entity
   *
   * @param  string|array $type
   * @param  string|null $text
   * @param  string|array|null $meta
   * @param  string|null $uri
   */
  abstract public function text($type, $text = null, $meta = null, $uri = null);

  /**
   * Generate container start token
   *
   * @param  string|array $type
   * @param  string|bool $label
   */
  public function startContain($type, $label = false){}

  /**
   * Generate container ending token
   */
  public function endContain(){}

  /**
   * Generate empty group token
   *
   * @param  string $prefix
   */
  public function emptyGroup($prefix = ''){}

  /**
   * Generate group start token
   *
   * This method must return boolean TRUE on success, false otherwise (eg. max depth reached).
   * The evaluator will skip this group on FALSE
   *
   * @param   string $prefix
   * @return  bool
   */
  public function startGroup($prefix = ''){}

  /**
   * Generate group ending token
   */
  public function endGroup(){}

  /**
   * Generate section title
   *
   * @param  string $title
   */
  public function sectionTitle($title){}

  /**
   * Generate row start token
   */
  public function startRow(){}

  /**
   * Generate row ending token
   */
  public function endRow(){}

  /**
   * Column divider (cell delimiter)
   *
   * @param  int $padLen
   */
  public function colDiv($padLen = null){}

  /**
   * Generate modifier tokens
   *
   * @param  array $items
   */
  public function bubbles(array $items){}

  /**
   * Input expression start
   */
  public function startExp(){}

  /**
   * Input expression end
   */
  public function endExp(){}

  /**
   * Root starting token
   */
  public function startRoot(){}

  /**
   * Root ending token
   */
  public function endRoot(){}

  /**
   * Separator token
   *
   * @param  string $label
   */
  public function sep($label = ' '){}

  /**
   * Resolve cache request
   *
   * If the ID is not present in the cache, then a new cache entry is created
   * for the given ID, and string offsets are captured until cacheLock is called
   *
   * This method must return TRUE if the ID exists in the cache, and append the cached item
   * to the output, FALSE otherwise.
   *
   * @param   string $id
   * @return  bool
   */
  public function didCache($id){
    return false;
  }

  /**
   * Ends cache capturing for the given ID
   *
   * @param  string $id
   */
  public function cacheLock($id){}

}

// modified version of RHtmlFormatter which doesn't have the nesting bug
// ideally this should be fixed upstream
// see RHtmlFormatter

/**
 * Generates the output in HTML5 format
 */

class RHtmlSpanFormatter extends RFormatter {

  public

    /**
     * Actual output
     *
     * @var  string
     */
    $out    = '',

    /**
     * Tracks current nesting level
     *
     * @var  int
     */
    $level  = 0,

    /**
     * Stores tooltip content for all entries
     *
     * To avoid having duplicate tooltip data in the HTML, we generate them once,
     * and use references (the Q index) to pull data when required;
     * this improves performance significantly
     *
     * @var  array
     */
    $tips   = array(),

    /**
     * Used to cache output to speed up processing.
     *
     * Contains hashes as keys and string offsets as values.
     * Cached objects will not be processed again in the same query
     *
     * @var  array
     */
    $cache  = array();



  protected static

    /**
     * Instance counter
     *
     * @var  int
     */
    $counter   = 0,

    /**
     * Tracks style/jscript inclusion state
     *
     * @var  bool
     */
    $didAssets = false;



  public function flush(){
    print $this->out;
    $this->out   = '';
    $this->cache = array();
    $this->tips  = array();
  }


  public function didCache($id){

    if(!isset($this->cache[$id])){
      $this->cache[$id] = array();
      $this->cache[$id][] = strlen($this->out);
      return false;
    }

    if(!isset($this->cache[$id][1])){
      $this->cache[$id][0] = strlen($this->out);
      return false;
    }

    $this->out .= substr($this->out, $this->cache[$id][0], $this->cache[$id][1]);
    return true;
  }

  public function cacheLock($id){
    $this->cache[$id][] = strlen($this->out) - $this->cache[$id][0];
  }


  public function sep($label = ' '){
    $this->out .= $label !== ' ' ? '<i>' . static::escape($label) . '</i>' : $label;
  }

  public function text($type, $text = null, $meta = null, $uri = null){

    if(!is_array($type))
      $type = (array)$type;

    $tip  = '';
    $text = ($text !== null) ? static::escape($text) : static::escape($type[0]);

    if(in_array('special', $type)){
      $text = strtr($text, array(
        "\r" => '<i>\r</i>',     // carriage return
        "\t" => '<i>\t</i>',     // horizontal tab
        "\n" => '<i>\n</i>',     // linefeed (new line)
        "\v" => '<i>\v</i>',     // vertical tab
        "\e" => '<i>\e</i>',     // escape
        "\f" => '<i>\f</i>',     // form feed
        "\0" => '<i>\0</i>',
      ));
    }

    // generate tooltip reference (probably the slowest part of the code ;)
    if($meta !== null){
      $tipIdx = array_search($meta, $this->tips, true);

      if($tipIdx === false)
        $tipIdx = array_push($this->tips, $meta) - 1;

      $tip = ' data-tip="' . $tipIdx . '"';
    }

    // wrap text in a link?
    if($uri !== null)
      $text = '<a href="' . $uri . '" target="_blank">' . $text . '</a>';

    //$this->out .= ($type !== 'name') ? "<span data-{$type}{$tip}>{$text}</span>" : "<span{$tip}>{$text}</span>";

    $typeStr = '';
    foreach($type as $part)
      $typeStr .= " data-{$part}";

    $this->out .= "<span{$typeStr}{$tip}>{$text}</span>";
  }

  public function startContain($type, $label = false){

    if(!is_array($type))
      $type = (array)$type;

    if($label)
      $this->out .= '<br>';

    $typeStr = '';
    foreach($type as $part)
      $typeStr .= " data-{$part}";

    $this->out .= "<span{$typeStr}>";

    if($label)
      $this->out .= "<span data-match>{$type[0]}</span>";
  }

  public function endContain(){
    $this->out .= '</span>';
  }

  public function emptyGroup($prefix = ''){

    if($prefix !== '')
      $prefix = '<span data-gLabel>' . static::escape($prefix) . '</span>';

    $this->out .= "<i>(</i>{$prefix}<i>)</i>";
  }


  public function startGroup($prefix = ''){

    $maxDepth = ref::config('maxDepth');

    if(($maxDepth > 0) && (($this->level + 1) > $maxDepth)){
      $this->emptyGroup('...');
      return false;
    }

    $this->level++;

    $expLvl = ref::config('expLvl');
    $exp = ($expLvl < 0) || (($expLvl > 0) && ($this->level <= $expLvl)) ? ' data-exp' : '';

    if($prefix !== '')
      $prefix = '<span data-gLabel>' . static::escape($prefix) . '</span>';

    $this->out .= "<i>(</i>{$prefix}<span data-toggle{$exp}></span><span data-group><span data-table>";

    return true;
  }

  public function endGroup(){
    $this->out .= '</span></span><i>)</i>';
    $this->level--;
  }

  public function sectionTitle($title){
    $this->out .= "</span><span data-tHead>{$title}</span><span data-table>";
  }

  public function startRow(){
    $this->out .= '<span data-row><span data-cell>';
  }

  public function endRow(){
    $this->out .= '</span></span>';
  }

  public function colDiv($padLen = null){
    $this->out .= '</span><span data-cell>';
  }

  public function bubbles(array $items){

    if(!$items)
      return;

    $this->out .= '<span data-mod>';

    foreach($items as $info)
      $this->out .= $this->text('mod-' . strtolower($info[1]), $info[0], $info[1]);

    $this->out .= '</span>';
  }

  public function startExp(){
    $this->out .= '<span data-input>';
  }

  public function endExp(){
    $this->out .= '</span><span data-output>';
  }

  public function startRoot(){
    $this->out .= '<!-- ref#' . static::$counter++ . ' --><div>' . static::getAssets() . '<div class="ref">';
  }

  public function endRoot(){
    $this->out .= '</span>';

    // process tooltips
    $tipHtml = '';
    foreach($this->tips as $idx => $meta){

      $tip = '';
      if(!is_array($meta))
        $meta = array('title' => $meta);

      $meta += array(
        'title'       => '',
        'left'        => '',
        'description' => '',
        'tags'        => array(),
        'sub'         => array(),
      );

      $meta = static::escape($meta);
      $cols = array();

      if($meta['left'])
        $cols[] = "<span data-cell data-varType>{$meta['left']}</span>";

      $title = $meta['title'] ?       "<span data-title>{$meta['title']}</span>"       : '';
      $desc  = $meta['description'] ? "<span data-desc>{$meta['description']}</span>"  : '';
      $tags  = '';

      foreach($meta['tags'] as $tag => $values){
        foreach($values as $value){
          if($tag === 'param'){
            $value[0] = "{$value[0]} {$value[1]}";
            unset($value[1]);
          }

          $value  = is_array($value) ? implode('</span><span data-cell>', $value) : $value;
          $tags  .= "<span data-row><span data-cell>@{$tag}</span><span data-cell>{$value}</span></span>";
        }
      }

      if($tags)
        $tags = "<span data-table>{$tags}</span>";

      if($title || $desc || $tags)
        $cols[] = "<span data-cell>{$title}{$desc}{$tags}</span>";

      if($cols)
        $tip = '<span data-row>' . implode('', $cols) . '</span>';

      $sub = '';
      foreach($meta['sub'] as $line)
        $sub .= '<span data-row><span data-cell>' . implode('</span><span data-cell>', $line) . '</span></span>';

      if($sub)
        $tip .= "<span data-row><span data-cell data-sub><span data-table>{$sub}</span></span></span>";

      if($tip)
        $this->out .= "<div>{$tip}</div>";
    }

    $this->out .= '</div></div><!-- /ref#' . static::$counter . ' -->';
  }



  /**
   * Get styles and javascript (only generated for the 1st call)
   *
   * @return  string
   */
  public static function getAssets(){

    // first call? include styles and javascript
    if(static::$didAssets)
      return '';

    ob_start();

    if(ref::config('stylePath') !== false){
      ?>
      <style scoped>
        <?php readfile(str_replace('{:dir}', __DIR__, ref::config('stylePath'))); ?>
      </style>
      <?php
    }

    if(ref::config('scriptPath') !== false){
      ?>
      <script>
        <?php readfile(str_replace('{:dir}', __DIR__, ref::config('scriptPath'))); ?>
      </script>
      <?php
    }

    // normalize space and remove comments
    $output = preg_replace('/\s+/', ' ', trim(ob_get_clean()));
    $output = preg_replace('!/\*.*?\*/!s', '', $output);
    $output = preg_replace('/\n\s*\n/', "\n", $output);

    static::$didAssets = true;
    return $output;
  }


  /**
   * Escapes variable for HTML output
   *
   * @param   string|array $var
   * @return  string|array
   */
  protected static function escape($var){
    return is_array($var) ? array_map('static::escape', $var) : htmlspecialchars($var, ENT_QUOTES);
  }

}