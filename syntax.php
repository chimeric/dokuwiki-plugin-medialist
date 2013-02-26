<?php
/**
 * DokuWiki Syntax Plugin Medialist
 *
 * Show a list of media files (images/archives ...) which are referenced
 * in a given page or which belong to the same namespace.
 *
 * Syntax:  {{medialist>[pagename]}}
 *
 *   [pagename] - a valid wiki pagename (use @PAGE@ for the current page)
 * 
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Michael Klier <chi@chimeric.de>
 * @author  Michael Braun <michael-dev@fami-braun.de>
 */
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_medialist extends DokuWiki_Syntax_Plugin {

    /**
     * General Info
     */
    function getInfo(){
        return array(
            'author' => 'Michael Klier',
            'email'  => 'chi@chimeric.de',
            'date'   => @file_get_contents(DOKU_PLUGIN.'medialist/VERSION'),
            'name'   => 'Medialist',
            'desc'   => 'Displays a list of media files linked from the given page or located in the namespace of the page.',
            'url'    => 'http://dokuwiki.org/plugin:medialist'
        );
    }

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
        $this->Lexer->addSpecialPattern('{{medialist>.+?}}',$mode,'plugin_medialist');
    }

    /**
     * Handler to prepare matched data for the rendering process
     */
    function handle($match, $state, $pos, &$handler){
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
     * @author Michael Braun <michael-dev@fami-braun.de>
     */
    function render($mode, &$renderer, $data) {
        
        if($mode == 'xhtml'){
            // disable caching
            $mode = $data[0];
            $id = $data[1];
            if(!empty($data[0])) {
                $renderer->info['cache'] = false;
                $renderer->doc .= $this->_medialist_xhtml($mode, $id, $renderer);
            }
            return true;
        }
        return false;
    }

    /**
     * Renders the medialist
     *
     * @author Michael Klier <chi@chimeric.de>
     * @author Michael Braun <michael-dev@fami-braun.de>
     */
    function _medialist_xhtml($mode, $id, &$renderer){
        $out  = '';

        $medialist = array();
        $media = $this->_media_lookup($mode, $id);

        if(empty($media)) return;

        // add list levels for html_buildlist
        foreach($media as $item) {
            array_push($medialist, array('id'=>$item, 'level'=>1, 'renderer' => $renderer));
        }

        $out .= html_buildlist($medialist,'medialist',array(&$this,'_media_item'));

        return ($out);
    }

    /**
     * Callback function for html_buildlist()
     *
     * @author Michael Braun <michael-dev@fami-braun.de>
     */
    function _media_item($item) {
        global $conf;

        $src = $item["id"];
        $renderer = $item["renderer"];
        list($src,$hash) = explode('#',$src,2);
        resolve_mediaid(getNS($ID),$src,$exists);

        $noLink = false;
        $link = $renderer->_getMediaLinkConf($src, NULL, NULL, NULL, NULL, NULL, true);

        list($ext,$mime,$dl) = mimetype($src,false);
        if(substr($mime,0,5) == 'image' && $render){
            $link['url'] = ml($src,array('id'=>$ID,'cache'=>$cache),($linking=='direct'));
        }elseif($mime == 'application/x-shockwave-flash' && $render){
            // don't link flash movies
            $noLink = true;
        }else{
            // add file icons
            $class = preg_replace('/[^_\-a-z0-9]+/i','_',$ext);
            $link['class'] .= ' mediafile mf_'.$class;
            $link['url'] = ml($src,array('id'=>$ID,'cache'=>$cache),true);
        }

        if($hash) $link['url'] .= '#'.$hash;

        //markup non existing files
        if (!$exists) {
            $link['class'] .= ' wikilink2';
        }

        //output formatted
        return $renderer->_formatLink($link);
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
            $dir = utf8_encode(str_replace(':','/', getNS($id)));
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
// vim:ts=4:sw=4:et:enc=utf-8:
