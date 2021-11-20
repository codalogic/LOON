<?php
include( '../loon.php' );
include( 'cl-phpunittest.php' );

$bs = '\\';

$loon = "
# Some fake details about me
com.codalogic.aboutme {
    Name:   Pete
    Height: 178
    DoB:    1969-04-18
    Children [
        {
        Name: Sarah
        Height: 170
        }
        {
        Name: Jenny
        Height: 144
        }
    ]
    Grades [
        A
        B
        C
    ]
    PlaceOfBirth: \" string with leading spaces! \"
    History <<END
        Born a long time again
        in a galaxy far, far away.
    <<END
}
";

//$array = LOON::from_string( $loon );

//var_dump( $array );

checkglobber();

checkreport();
?>
