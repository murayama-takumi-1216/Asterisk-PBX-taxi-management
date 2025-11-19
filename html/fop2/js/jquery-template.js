/*!
 * jQuery Template
 * November 5, 2009
 * Corey Hart @ http://www.codenothing.com
 *
 * A port of Prototype's templating system @ http://www.prototypejs.org/api/template
 */
;(function($, undefined){
var
    // Variable Pattern Match
    Pattern = /(^|.|\r|\n)(#\{(.*?)\})/,

    // Template function, returns an object that contains
    // the template and the pattern
    Template = function(template, pattern){
        // Join array based template passed
        this.template = $.isArray(template) ? template.join('') : template||'';

        // Set user defined pattern, or just use base pattern
        this.pattern = pattern||Pattern;

        // Add evaluation function that keeps original template intact, 
        // while returning converted temp
        this.eval = function(obj){
            var temp = this.template, lastIndex = 0, match, m;

            // Ensure object format
            if (obj === undefined || typeof obj !== 'object')
                obj = {};

            // All patterns matched need to be replaced with their respective values, or
            // with an empty string. When looping, be sure to only execute the pattern
            // on the part of the string that has yet to be transformed
            while (match = this.pattern.exec(temp.substr(lastIndex))){
                // Pass over escaped formats and remove their lingering '\'
                if (match[1] === "\\"){
                    lastIndex = temp.indexOf(match[0]) + match[0].length;
                    temp = temp.replace(match[0], match[0].substr(1));
                }else{
                    m = match[3];
                    lastIndex = temp.indexOf(match[0]) + (obj[m] ? obj[m].length : 0);
                    temp = temp.replace(match[2], obj[m] ? obj[m] : '');
                }
            }
            return temp;
        }
    };

    // Attach template to jQuery
    $.template = function(template, pattern){
        return new Template(template, pattern);
    };
})(jQuery);
