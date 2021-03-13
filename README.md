# LOON
LOON - Line Oriented Object Notation

LOON is a simple file format for configuration data.  It is intended to be easy for both humans and machines to read and write. It is a stripped-down form of JSON, that ends up looking similar to the format used by HTTP, SMTP etc.

An example LOON message is as follows:
```
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
    PlaceOfBirth: " string with leading spaces! "
    History <<END
        Born a long time again
        in a galaxy far, far away.
    <<END
}
```
