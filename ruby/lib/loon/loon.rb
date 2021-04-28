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

module LOON
    class Error
        def initialize msg
            @message = msg
        end

        def error
            @message
        end
    end

    class LOONParser
        STATE_INIT = :state_init
        STATE_IN_OBJECT = :state_in_object
        STATE_IN_ARRAY = :state_in_array
        STATE_IN_MULTISTRING = :state_in_multistring
        STATE_COMPLETE = :state_complete
        STATE_ERRORED = :state_errored

        def initialize
            reset
        end

        def reset
            @state = STATE_INIT
            @line_num = 0
            @stack = []
            @base = {}
            @current = nil
            @is_naked = false
            @error_message = ''
            @multistring_string = ''
            @multistring_end = ''
        end

        def parse_line line
            @line_num += 1
            line.strip! if @state != STATE_IN_MULTISTRING
            if line != "" && line[0] != '#'
                case @state
                    when STATE_INIT
                        when_init line
                    when STATE_IN_OBJECT
                        when_in_object line
                    when STATE_IN_ARRAY
                        when_in_array line              
                    when STATE_IN_MULTISTRING
                        when_in_multistring line
                    else
                        # Do nothing
                        return false
                end
            end
            return true
        end

        def result
            @base
        end

        def is_finished     # Only applies for non-naked object form
            @state == STATE_COMPLETE || @state == STATE_ERRORED
        end

        def is_good
            @state != STATE_ERRORED
        end

        def error
            @error_message
        end

        private

        def when_init line
            if line == '{'
                @base = @current = {}
                @state = STATE_IN_OBJECT
            elsif line == '['
                @base = @current = []
                @state = STATE_IN_ARRAY
            else
                @base = @current = {}
                @state = STATE_IN_OBJECT
                @is_naked = true
                when_in_object line
            end
        end

        REGEX_MEMBER_SPLIT = /([\w\.]+)\s*(.*)/

        def when_in_object line
            if( m = line.match( REGEX_MEMBER_SPLIT ) )
                name = m[1]
                rhs = m[2]
                if @current.include? name
                    record_error "Duplicate name in object"
                elsif rhs == ''
                    @current[name] = nil
                elsif rhs[0] == ':'
                    @current[name] = parse_inline_string_value rhs[1..-1].strip
                else
                    my_current = @current  # parse_value may change @current
                    my_current[name] = parse_value rhs
                end
            elsif line == '}'
                pop_stack
            elsif line == ']'
                record_error "Unexpected array close (\"]\") in object"
            else
                record_error "Illegal member format in object: #{line}"
            end
        end

        def when_in_array line
            if line == ']'
                pop_stack
            elsif line == '}'
                record_error "Unexpected object close (\"}\") in array"
            else
                my_current = @current  # parse_value may change @current
                my_current.push parse_value line
            end
        end

        REGEX_MULITILINE_END = /(.*)<<\w+$/

        def when_in_multistring line
            test_line = line.rstrip
            if( test_line.end_with?( @multistring_end ) && m = test_line.match( REGEX_MULITILINE_END ) )
                line = m[1]
                pop_stack
            end
            @multistring_string.concat "\n" if @multistring_string != ''
            @multistring_string.concat( string_unescape line.chomp )    # Remove any newlines but leave other trailing whitespace
        end

        REGEX_MULITILINE_START = /^<<\w+$/

        def parse_value value
            if value == '{'
                @stack.push [ @current, @state ]
                @current = {}
                @state = STATE_IN_OBJECT
                return @current
            elsif value == '['
                @stack.push [ @current, @state ]
                @current = []
                @state = STATE_IN_ARRAY
                return @current
            elsif value =~ REGEX_MULITILINE_START    # Multiline string
                @stack.push [ @current, @state ]
                @current = ""
                @state = STATE_IN_MULTISTRING
                @multistring_string = ''
                @multistring_end = value
                return @multistring_string
            else
                if @state == STATE_IN_ARRAY
                    return parse_inline_string_value value
                elsif @state == STATE_IN_OBJECT
                    record_error "Value without preceding : in object"
                    return nil
                else
                    record_error "Bad value format"    # Should be impossible
                    return nil
                end
            end
        end

        def parse_inline_string_value value
            if value == '\\0'
                return nil
            elsif value[0] == '"' && value[-1] == '"'   # Quoted string
                return string_unescape value[1...-1]
            else
                return string_unescape value    # Naked string - It's up to app to decide if it's an integer, bool etc.
            end
        end

        # escaped = escape (
        #    escape / ; \  i.e.: \\ -> \
        #    ; N.B. quotation-mark is NOT escaped
        #    %x62 / ; b  i.e.: \b -> backspace
        #    %x66 / ; f  i.e.: \f -> form feed
        #    %x6E / ; n  i.e.: \n -> line feed
        #    %x72 / ; r  i.e.: \r -> carriage return
        #    %x74 / ; t  i.e.: \t -> tab
        #    %x75 (4HEXDIG / "{" 1*6HEXDIG "}")
        #         ; \uXXXX or \u{XXXXXX} -> U+XXXX

        ESCAPE_MAP = {
            '\\' => "\\",
            'b'  => "\b",
            'f'  => "\f",
            'n'  => "\n",
            'r'  => "\r",
            't'  => "\t"
        }

        REGEX_4_DIGIT_UNICODE_CODE_EXTRACTOR = /u([0-9a-fA-F]{4})(.*)/
        REGEX_MULTI_DIGIT_UNICODE_CODE_EXTRACTOR = /u\{([0-9a-fA-F]{1,6})\}(.*)/

        def string_unescape s
            output = ''
            remainder = s
            while remainder != ''
                lhs, rhs = remainder.split '\\', 2
                output.concat lhs
                if rhs.nil? || rhs == ''
                    remainder = ''
                elsif rhs.length < 2
                    record_error "Illegal backslash at end of string: #{s}"
                    return output
                elsif ESCAPE_MAP.include? rhs[0]
                    output.concat ESCAPE_MAP[rhs[0]]
                    remainder = rhs[1..-1]
                elsif rhs[0] == 'u'
                    if( m = rhs.match( REGEX_MULTI_DIGIT_UNICODE_CODE_EXTRACTOR ) )
                        hexcode = m[1]
                        remainder = m[2]
                        output.concat hexcode.to_i(16).chr('UTF-8')
                    else
                        codepoint, remainder = extract_utf16_escape_sequence rhs
                        return output if codepoint.nil?    # Error recorded already
                        output.concat codepoint.chr('UTF-8')
                    end
                else
                    record_error "Illegal backslash sequence in string: \\#{rhs[0]}"
                    return output
                end
            end
            return output
        end

        def extract_utf16_escape_sequence s     # Supports UTF-16 surrogates
            codepoint, remainder = extract_utf16_escape_codepoint s
            return [codepoint, remainder] if codepoint.nil?     # An error occured - error already recorded
            if codepoint >= 0x0000dc00 && codepoint <= 0x0000dfff
                record_error "Illegal low UTF-16 surrogate found without preceding high surrogate: #{s[0..5]}"
                return [nil, remainder]
            end
            if codepoint >= 0x0000d800 && codepoint <= 0x0000dbff      # High surrogate found
                if remainder[0] != '\\'
                    record_error "Expected low surrogate after high surrogate in: #{s[0..11]}"
                    return [nil, remainder]
                else
                    remainder = remainder[1..-1]    # Remove leading \. Now should be left with uXXXXX...
                    low_codepoint, remainder = extract_utf16_escape_codepoint remainder
                    if low_codepoint.nil? || low_codepoint < 0x0000dc00 || low_codepoint > 0x0000dfff
                        record_error "Expected low surrogate after high surrogate in: #{s[0..11]}"
                        return [nil, remainder]
                    end
                    codepoint = ((codepoint & 0x3ff)<<10) + (low_codepoint & 0x3ff) + 0x10000
                end
            end
            return [codepoint, remainder]
        end

        def extract_utf16_escape_codepoint s    # Single \uXXXX code only - \ already removed so parsing uXXXX
            if( m = s.match( REGEX_4_DIGIT_UNICODE_CODE_EXTRACTOR ) )
                hexcode = m[1]
                remainder = m[2]
                codepoint = hexcode.to_i(16)
            else
                record_error "Illegal \\uXXXX escape sequence in: #{s[0..5]}"
                codepoint = nil
                remainder = ''
            end
            return [codepoint, remainder]
        end

        def pop_stack
            if ! @stack.empty?
                @current, @state = @stack.pop
            elsif ! @is_naked
                @state = STATE_COMPLETE
            else
                record_error "Unexpected '}' or '}' encountered"
            end
        end

        def record_error msg
            @error_message = "Line #{@line_num}: #{msg}"
            @state = STATE_ERRORED
        end
    end

    def self.parse str
        parser = LOONParser.new
        str.lines.each { |l| parser.parse_line l }
        if ! parser.is_good
            return Error.new parser.error
        end
        return parser.result
    end

    def self.from_file fname
        parser = LOONParser.new
        File.foreach( fname ) do |line|
            parser.parse_line line
        end
        if ! parser.is_good
            return Error.new parser.error
        end
        return parser.result
    end
end
