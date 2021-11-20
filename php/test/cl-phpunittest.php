<?php
$checkfails = $checktests = 0;
$checkfout = NULL;
$checktodos = [];
$checkwrap = 0;

function checkglobber()
{
    foreach( glob( 'test-*.php' ) as $test_file )
        include_once( $test_file );
    // Note: Using `include_once()` will ensure that the file that calls `checkglobber()` will not be re-included
}

function checkfeature( $heading, $func )
{
    global $checkwrap;

    checkheading( $heading );
    $org_checkwrap = $checkwrap;
    $func();
    $checkwrap = $org_checkwrap;
}

function  checkheading( $heading )
{
    global $checktests;

    $heading .= " at: " . checkcallsite();
    $underline = str_repeat( "=", strlen( $heading ) );
    if( $checktests != 0 )
        checkprint( "\n" );
    checkprint(
        "       $heading\n" .
        "       $underline\n" );
}

function check( $what, $result, $expected = NULL )
{
    # Allow $what to be optional
    if( ! isset( $expected ) ) {
        $expected = $result;
        $result = $what;
    }

    checkcompare( $what, $result, $expected );
}

function checkeval( $what, $expected )
{
    $result = eval( "return " . checkdisplayable( $what ) . ";" );

    checkcompare( $what, $result, $expected );
}

function checkstr( $what, $result, $expected = NULL )
{
    // checkstr() is now a legacy function due to introduction of checkeval
    check( $what, $result, $expected );
}

function checkrtrim( $what, $result, $expected = NULL )
{
    check( rtrim( $what ), rtrim( $result ), isset( $expected ) ? rtrim( $expected ) : NULL );
}

function checkevalrtrim( $what, $expected )
{
    $result = eval( "return " . checkdisplayable( $what ) . ";" );

    checkcompare( rtrim( $what ), rtrim( $result ), rtrim( $expected ) );
}

function checkstrrtrim( $what, $result, $expected = NULL )
{
    // checkstrrtrim() is now a legacy function due to introduction of checkeval
    checkrtrim( $what, $result, $expected );
}

function checkfailed( $reason )
{
    global $checkfails, $checktests;
    $checktests++;
    $checkfails++;
    checkprint( "Not ok: \"$reason\" at " . checkcallsite() . "\n" );
}

function checktodo( $task )
{
    global $checktodos;

    $task = "\"$task\" at " . checkcallsite();
    checkprint( "  TODO: $task\n" );
    $checktodos[] = $task;
}

function checkcompare( $what, $result, $expected )
{
    global $checkfails, $checktests;
    $checktests++;

    if( $result === $expected ) {
        checkprint( "    ok: " . checkdisplayable( $what ) . " --> " . checkdisplayable( $expected ) . "\n" );
    }
    else {
        checkprint( "Not ok: With:     $what\n" .
                    "        Expected: " . checkdisplayable( $expected ) . "\n" .
                    "        Got:      " . checkdisplayable( $result ) . "\n" .
                    "        at:       " . checkcallsite() . "\n" );
        $checkfails++;
    }
}

function checkdisplayable( $v )
{
    if( is_bool( $v ) )
        return $v ? "True" : "False";
    return strval( $v );
}

function checkcallsite( $want_line_num = True )
{
    $backtrace = debug_backtrace();
    $index = 0;
    while( $backtrace[$index]["file"] == __FILE__ )
        ++$index;
    if( $want_line_num )
        return basename( $backtrace[$index]["file"] ) . "(" . $backtrace[$index]["line"] . ")";
    return basename( $backtrace[$index]["file"] );
}

function checkcallfile()
{
    return checkcallsite( False );
}

function checkwrap( $wrap_length = NULL )
{
    global $checkwrap;

    $old_wrap = $checkwrap;
    if( isset( $wrap_length ) )
        $checkwrap = $wrap_length;
    return $old_wrap;
}

function checkwrap__do_wrapping( $message )
{
    $parts = explode( "\n", $message );
    $wrapped_parts = [];
    foreach( $parts as $p )
        $wrapped_parts[] = checkwrap__do_line_wrapping( $p );
    return implode( "\n", $wrapped_parts );
}

function checkwrap__do_line_wrapping( $line )
{
    global $checkwrap;

    static $wrap_insertion = "\n            ";
    $min_warp = strlen( $wrap_insertion ) + 2;   # Need to accept more on each iteration than we insert in to avoid infinite insertions

    if( $checkwrap > $min_warp ) {
        $next_insert = $checkwrap;
        while( strlen( $line ) > $next_insert ) {
            if( ! ctype_space( $line[$next_insert] ) ) {
                $next_word_insert = $next_insert;
                $max_backtracks = $checkwrap - $min_warp;
                while( $max_backtracks > 0 && ! ctype_space( $line[$next_word_insert] ) ) {
                    --$next_word_insert;
                    --$max_backtracks;
                }
                if( $max_backtracks > 0 )
                    $next_insert = $next_word_insert;
            }
            $replace_count = 0;
            while( ctype_space( $line[$next_insert + $replace_count] ) )
                ++$replace_count;
            $line = substr_replace( $line, $wrap_insertion, $next_insert, $replace_count );
            $next_insert += $checkwrap + 1;     # +1 for invisible \n inserted
        }
    }
    return $line;
}

function checkprint( $message )
{
    global $checkfout, $checkwrap;

    if( $checkwrap > 0 )
        $message = checkwrap__do_wrapping( $message );
    if( ! isset( $checkfout ) )
        $checkfout = fopen( "check-out.txt", "wt" );
    if( $checkfout !== FALSE )
        fwrite( $checkfout, $message );
    print( $message );
}

function checkoutputclose()
{
    if( isset( $checkfout ) && $checkfout !== FALSE ) {
        fclose( $checkfout );
        $checkfout = FALSE;
    }
}

function checkreport()
{
    global $checkfails, $checktests, $checktodos;

    $num_todos = count( $checktodos );
    if( $num_todos > 0 ) {
        checkprint( "\n    TODOs:\n");
        foreach( $checktodos as $task )
            checkprint( "    - $task\n" );
    }

    checkprint( "\n$checkfails fails, $num_todos todos, $checktests tests\n" );
}

class CheckStopwatch {
    private $start_time = 0;
    private $stop_time = 0;

    function __construct() {
        $this->Start();
    }

    public function Start() {
        $this->stop_time = 0;
        $this->start_time = microtime(True);
    }

    public function Stop() {
        $this->stop_time = microtime(True);
    }

    public function ElapsedMilliSeconds() {
        $end_time = $this->stop_time;
        if( $end_time == 0 )
            $end_time = microtime(True);
        return (int)(($end_time - $this->start_time) * 1000.0);
    }
}
?>
