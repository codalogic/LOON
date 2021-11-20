#----------------------------------------------------------------------------
# Licensed under the MIT/X11 license - https://opensource.org/licenses/MIT
#----
# Copyright (c) 2021, Codalogic Ltd (www.codalogic.com)
#
# Permission is hereby granted, free of charge, to any person obtaining a
# copy of this software and associated documentation files (the "Software"),
# to deal in the Software without restriction, including without limitation
# the rights to use, copy, modify, merge, publish, distribute, sublicense,
# and/or sell copies of the Software, and to permit persons to whom the
# Software is furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included
# in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
# THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
# FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
# DEALINGS IN THE SOFTWARE.
#----------------------------------------------------------------------------

require 'rspec'
require_relative '../lib/loon'

$bs = "\\"

describe 'loon' do
    it 'should return a string if given an object with a naked string' do
        v = LOON.parse <<-End
            {
                s : My string here
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "My string here"
    end

    it 'should return a string if given an object with a quoted string with leading a trailing whitespace' do
        v = LOON.parse <<-End
            {
                s : "  My string here  "
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "  My string here  "
    end

    it 'should return a string if given an object with a quoted string with internal quotation marks' do
        v = LOON.parse <<-End
            {
                s : "My string here with ""
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "My string here with \""
    end

    it 'should return a string if given an object with a multi line string' do
        v = LOON.parse <<-End
            {
                s <<END
                My string
                Other string
                <<END
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'].lstrip.gsub( /\n\s*/, "\n" ) ).to eq "My string\nOther string\n"
    end

    it 'should return a string if given an object with a multi line string without final end-of-line' do
        v = LOON.parse <<-End
            {
                s <<END
                My string
                Other string <<END
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'].lstrip.gsub( /\n\s*/, "\n" ) ).to eq "My string\nOther string "
    end

    it 'should return a string that includes comment text if given an object with a multi line string with comment text' do
        v = LOON.parse <<-End
            {
                s <<END
                My string
# Not a comment as it's in a multi-line string
                Other string <<END
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'].lstrip.gsub( /\n\s*/, "\n" ) ).to eq "My string\n# Not a comment as it's in a multi-line string\nOther string "
    end

    it 'should return a string with a tab in if given an object with a string with a \t' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s : String with '#{$bs}t' in the middle
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "String with '\u0009' in the middle"     # \u0009 == \t == TAB
    end

    it 'should return a string with a tab in if given an object with a string with a \u0009' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s : String with '#{$bs}u0009' in the middle
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "String with '\t' in the middle"     # \t == \u0009 == TAB
    end

    it 'should return a string with back-to-back tabs in if given an object with a string with a \t\u0009' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s : String with '#{$bs}t#{$bs}u0009' in the middle
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "String with '\u0009\t' in the middle"     # \t == \u0009 == TAB
    end

    it 'should return a string with a euro symbol in if given an object with a string with a \u0009' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s : String with #{$bs}u20ac in the middle
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "String with \u20ac in the middle"
    end

    it 'should return a string with non-surrogate if given an object with a string with a surrogate pair' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s : String with #{$bs}uD800#{$bs}uDEAD in the middle
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "String with \u{102AD} in the middle"
    end

    it 'should return a string with non-BMP character if given an object with a string with a \u{XXXXXX} code' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s : String with #{$bs}u{0102AD} in the middle
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "String with \u{102AD} in the middle"
    end

    it 'should return a string with a tab if given an object with a string with a \u{XXXXXX} code' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s : String with '#{$bs}u{9}' in the middle
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "String with '\t' in the middle"     # \t == \u0009 == TAB
    end

    it 'should return a string with a \u{102AD} in the middle' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s : String with #{$bs}u{0102AD} in the middle
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "String with \u{102AD} in the middle"
    end

    it 'should return a string with a tab in if given an object with a multi line string with a \t' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s <<END
                String with '#{$bs}t' in the middle
                <<END
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'].strip ).to eq "String with '\u0009' in the middle"     # \u0009 == \t == TAB
    end

    it 'should return a string with a tab in if given an object with a multi line string with a \u0009' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s <<END
                String with '#{$bs}u0009' in the middle
                <<END
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'].strip ).to eq "String with '\t' in the middle"     # \t == \u0009 == TAB
    end

    it 'should return a string with a euro symbol in if given an object with a multi line string with a \u0009' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s <<END
                String with #{$bs}u20ac in the middle
                <<END
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'].strip ).to eq "String with \u20ac in the middle"
    end

    it 'should return a string with non-surrogate if given an object with a multi line string with a surrogate pair' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s <<END
                String with #{$bs}uD800#{$bs}uDEAD in the middle
                <<END
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'].strip ).to eq "String with \u{102AD} in the middle"
    end

    it 'should return a string with non-BMP character if given an object with a multi line string with a \u{XXXXXX} code' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s <<END
                String with #{$bs}u{0102AD} in the middle
                <<END
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'].strip ).to eq "String with \u{102AD} in the middle"
    end

    it 'should return a string with tab character if given an object with a multi line string with a \u{9} code' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                s <<END
                String with #{$bs}u{9} in the middle
                <<END
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'].strip ).to eq "String with \t in the middle"
    end
end
