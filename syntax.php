<?php
/**
 * DokuWiki Syntax Plugin Medialist
 *
 * Show a list of media files (images/archives ...) referred in a given page
 * or stored in a given namespace.
 *
 * Syntax:  {{medialist>[id]}}
 *          {{medialist>[ns]:}} or {{medialist>[ns]:*}}
 *
 *   [id] - a valid page id (use @ID@ for the current page)
 *   [ns] - a namespace (use @NS@: for the current namespace)
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
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        global $ID;

        // catch the match
        $match = substr($match, 12, -2);

        // process the match
        $params = array();

        // v1 syntax (backword compatibility for 2009-05-21 release)
        // @PAGE@, @NAMESPACE@, @ALL@ are complete keyword arguments,
        // not replacement patterns.
        switch ($match) {
            case '@PAGE@':
                $params = array('scope' => 'page', 'id' => $ID );
                break;
            case '@NAMESPACE@':
                $params = array('scope' => 'ns',   'id' => getNS($ID) );
                break;
            case '@ALL@':
            case '@BOTH@':
                $params = array('scope' => 'both', 'id' => $ID );
                break;
        }

        // v2 syntax (available since 2016-06-XX release)
        // - enable replacement patterns @ID@, @NS@, @PAGE@
        //   for media file search scope
        // - Namespace search if scope parameter ends colon ":", and
        //   require "*" after the colon for recursive search
        if (empty($params)) {
            $target = trim($match);

            // namespace searach options
            if (substr($target, -2) == ':*') {
                $params['scope']  = 'ns';  // not set depth option
            } elseif (substr($target, -1) == ':') {
                $params['scope']  = 'ns';
                $params['depth'] = 1;
            } else {
                $params['scope']  = 'page';
            }
            $target = rtrim($target, ':*');

            // replacement patterns identical with Namespace Template
            // @see https://www.dokuwiki.org/namespace_templates#syntax
            $target = str_replace('@ID@', $ID, $target);
            $target = str_replace('@NS@', getNS($ID), $target);
            $target = str_replace('@PAGE@', noNS($ID), $target);

            $params['id'] = cleanID($target);
        }

        return array($state, $params);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {

        if ($format == 'xhtml'){
            // disable caching
            $renderer->info['cache'] = false;
            $renderer->doc .= $this->render_xhtml($data);
            return true;
        }
        return false;
    }

    /**
     * Renders xhtml
     */
    protected function render_xhtml($data) {
        $out  = '';
        $items = array();

        list($state, $params) = $data;
        $id    = $params['id'];
        $scope = $params['scope'];

        // search option for lookup_stored_media()
        if (array_key_exists('depth', $params)) {
            $opt = array('depth' => $params['depth']);
        } else {
            $opt   = array();
        }

        switch ($scope) {
            case 'page':
                $media = $this->_lookup_linked_media($id);
                foreach ($media as $item) {
                    $items[] = array('level'=> 1, 'id'=> $item, 'base'=> getNS($item));
                }
                break;
            case 'ns':
                $media = $this->_lookup_stored_media($id, $opt);
                foreach ($media as $item) {
                    $items[] = array('level'=> 1, 'id'=> $item, 'base'=> $id);
                }
                break;
            case 'both':
                $linked_media = $this->_lookup_linked_media($id);
                $stored_media = $this->_lookup_stored_media(getNS($id), $opt);
                $media = array_unique(array_merge($stored_media, $linked_media));
                foreach ($media as $item) {
                    if (in_array($item, $linked_media)) {
                        $items[] = array('level'=> 1, 'id'=> $item, 'base'=> $id, 'linked'=> 1);
                    } else {
                        $items[] = array('level'=> 1, 'id'=> $item, 'base'=> $id);
                    }
                }
                break;
        }

        if (!empty($items)) {
            $out .= html_buildlist($items, 'medialist', array($this, '_media_item'));
        } else {
            $out .= '<div class="medialist info">';
            $out .= '<strong>'.$this->getPluginName().'</strong>'.': nothing to show here.';
            $out .= '</div>';
        }
        return $out;
    }

    /**
     * Callback function for html_buildlist()
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _media_item($item) {
        global $conf, $lang;

        $out = '';

        $link = array();
        $link['url']    = ml($item['id']);
        $link['class']  = isset($item['linked']) ? 'media linked' : 'media';
        $link['target'] = $conf['target']['media'];
        $link['title']  = noNS($item['id']);
        $link['name']   = str_replace($item['base'].':','', $item['id']);

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
        if (preg_match('#^https?://#', $item['id'])) {
            $out .= $lang['qb_extlink']; // External Link
        } else {
            $out .= strftime($conf['dformat'], filemtime(mediaFN($item['id']))).'&nbsp;';
            $out .= filesize_h(filesize(mediaFN($item['id'])));
        }
        $out .= ')</span>' . DOKU_LF;

        return $out;
    }

    /**
     * searches media files linked in the given page
     * returns an array of items
     */
    protected function _lookup_linked_media($id) {
        $linked_media = array();

        if (!page_exists($id)) {
            //msg('MediaList: page "'. hsc($id) . '" not exists!', -1); 
        }

        if (auth_quickaclcheck($id) >= AUTH_READ) {
            // get the instructions
            $ins = p_cached_instructions(wikiFN($id), true, $id);

            // get linked media files
            foreach ($ins as $node) {
                if ($node[0] == 'internalmedia') {
                    $linked_media[] = cleanID($node[1][0]);
                } elseif ($node[0] == 'externalmedia') {
                    $linked_media[] = $node[1][0];
                }
            }
        }
        return array_unique($linked_media);
    }

    /**
     * searches media files stored in the given namespace and sub-tiers
     * returns an array of items
     */
    protected function _lookup_stored_media($ns, $opt=array('depth'=>1)) {
        global $conf;

        $intern_media = array();

        $dir = utf8_encodeFN(str_replace(':','/', $ns));

        if (!is_dir($conf['mediadir'] . '/' . $dir)) {
            //msg('MediaList: namespace "'. hsc($ns). '" not exists!', -1);
        }

        if (auth_quickaclcheck("$ns:*") >= AUTH_READ) {
            // get mediafiles of current namespace
            $res = array(); // search result
            search($res, $conf['mediadir'], 'search_media', $opt, $dir);

            foreach ($res as $item) {
                $intern_media[] = $item['id'];
            }
        }
        return $intern_media;
    }

}
