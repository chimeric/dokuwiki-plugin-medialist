<?php
/**
 * DokuWiki Syntax Plugin Medialist
 *
 * Show a list of media files (images/archives ...) referred in a given page
 * using curly brackets "{{...}}", or stored in a given namespace.
 *
 * Syntax:  {{medialist>[id]}}
 *          {{medialist>[ns]}}
 *
 *   [id] - a valid wiki page id (use @PAGE@ for the current page)
 *   [ns] - a namespace (use @NAMESPACES@ for the current namespace)
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Michael Klier <chi@chimeric.de>
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 */
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_medialist extends DokuWiki_Syntax_Plugin {

    /**
     * Syntax Type
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     */
    function getType()  { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort()  { return 299; }
    
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{medialist>[^\r\n]+?}}',$mode,'plugin_medialist');
    }

    /**
     * Handler to prepare matched data for the rendering process
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        global $ID;

        // catch the match
        $match = substr($match,12,-2);

        // process the match
        if($match == '@PAGE@') {
            $mode = 'page';
            $id = $ID;
        } elseif($match == '@NAMESPACE@') {
            $mode = 'ns';
            $id = $ID;
        } elseif($match == '@ALL@') {
            $mode = 'all';
            $id = $ID;
        } elseif(@page_exists(cleanID($match))) {
            $mode = 'page';
            $id = $match;
        }

        return array($mode, $id);
    }

    /**
     * Handles the actual output creation.
     */
    function render($format, Doku_Renderer $renderer, $data) {
        
        if($format == 'xhtml'){
            // disable caching
            $mode = $data[0];
            $id = $data[1];
            if(!empty($data[0])) {
                $renderer->info['cache'] = false;
                $renderer->doc .= $this->_medialist_xhtml($mode, $id);
            }
            return true;
        }
        return false;
    }

    /**
     * Renders the medialist
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _medialist_xhtml($mode, $id){
        $out  = '';

        $medialist = array();
        $media = $this->_media_lookup($mode, $id);

        if(empty($media)) return;

        // add list levels for html_buildlist
        foreach($media as $item) {
            array_push($medialist, array('id'=>$item, 'level'=>1));
        }

        $out .= html_buildlist($medialist,'medialist',array(&$this,'_media_item'));

        return ($out);
    }

    /**
     * Callback function for html_buildlist()
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _media_item($item) {
        global $conf;

        $out = '';

        $link = array();
        $link['url']    = ml($item['id']);
        $link['class']  = 'media';
        $link['target'] = $conf['target']['media'];
        $link['name']   = preg_replace('#.*?/|.*?:#','',$item['id']);
        $link['title']  = $link['name'];

        // add file icons
        list($ext,$mime) = mimetype($item['id']);
        $class = preg_replace('/[^_\-a-z0-9]+/i','_',$ext);
        $link['class'] .= ' mediafile mf_'.$class;

        // build the link
        $out .= '<a href="' . $link['url'] . '" ';
        $out .= 'class="' . $link['class'] . '" ';
        $out .= 'target="' . $link['target'] . '" ';
        $out .= 'title="' . $link['title'] . '">';
        $out .= $link['name'];
        $out .= '</a>';
        $out .= '&nbsp;<span class="mediainfo">(';
        $out .= date("Y/m/d H:i:s", filemtime(mediaFN($item['id']))).'&nbsp;';
        $out .= filesize_h(filesize(mediaFN($item['id'])));
        $out .= ')</span>' . DOKU_LF;

        return ($out);
    }

    /**
     * searches for media linked in the page and its namespace and
     * returns an array of items
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _media_lookup($mode, $id) {
        global $conf;

        $media = array();
        $linked_media = array();
        $intern_media = array();

        if(($mode == 'page') or ($mode == 'all')) {
            // check permissions for the page
            if(auth_quickaclcheck($id) >= AUTH_READ) {
                // get the instructions
                $ins = p_cached_instructions(wikiFN($id),true,$id);

                // get linked media files
                foreach($ins as $node) {
                    if($node[0] == 'internalmedia') {
                        array_push($linked_media,$node[1][0]);
                    } elseif($node[0] == 'externalmedia') {
                        array_push($linked_media,$node[1][0]);
                    }
                }
            }
        }

        if(($mode == 'ns') or ($mode == 'all')) {
            $dir = utf8_encodeFN(str_replace(':','/', getNS($id)));
            if(@is_dir($conf['mediadir'] . '/' . $dir)) {
                if(auth_quickaclcheck($dir) >= AUTH_READ) {
                    // get mediafiles of current namespace
                    $res = array(); // search result
                    require_once(DOKU_INC.'inc/search.php');
                    search($res,$conf['mediadir'],'search_media',array(),$dir);
                    foreach($res as $item) {
                        array_push($intern_media,$item['id']);
                    }
                }
            }
        }

        // remove unique items
        $media = array_unique(array_merge($linked_media,$intern_media));

        return($media);
    }
}
