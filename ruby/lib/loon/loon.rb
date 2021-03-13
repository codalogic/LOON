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
    class LOONParser
        STATE_INIT = :state_init
        STATE_IN_OBJECT = :state_in_object
        STATE_IN_ARRAY = :state_in_array
        STATE_IN_MULTISTRING = :state_in_multistring
        STATE_COMPLETE = :state_complete
        STATE_ERRORED = :state_errored

        def initialize
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

        def when_in_object line
            if line == '}'
                pop_stack
            elsif( m = line.match( /([\w\.]+)\s*(.*)/ ) )
                name = m[1]
                rhs = m[2]
                if @current.include? name
                    record_error "Duplicate name in object"
                elsif value[0] == ':'
                    @current[name] = parse_inline_string_value rhs[1..-1].strip
                else
                    my_current = @current  # parse_value may change @current
                    my_current[name] = parse_value rhs
                end
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

        def when_in_multistring line                    
            if( m = line.match( /(.*+)<<\w+$/ ) )
                line = m[1]
                pop_stack
            end
            @multistring_string.concat "\n" if @multistring_string == ''
            @multistring_string.concat( string_unescape line )
        end

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
            elsif value =~ /^<<\w+$/    # Multiline string
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
            if value[0] == '"' && value[-1] == '"'   # Quoted string
                return string_unescape value[1...-1]
            else
                return string_unescape value    # Naked string - It's up to app to decide if it's an integer, bool etc.
            end
        end

        def string_unescape s
            # TODO
            return s
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
        return nil if ! parser.is_good
        return parser.result
    end

    def self.from_file fname
        parser = LOONParser.new
        File.foreach( fname ) do |line|
            parser.parse_line line
        end
        return nil if ! parser.is_good
        return parser.result
    end
end
