<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
 * @copyright       Ersin Guvenc (c) 2009.
 * @filesource
 * @license
 */

// ------------------------------------------------------------------------
 
require_once 'Pager_common.php';

/**
 * Obullo Pager Jumping Driver
 *
 *
 * @package       Obullo
 * @subpackage    Libraries.drivers.Pager_jumping
 * @category      Libraries
 * @author        Ersin Guvenc
 * @author        Derived from PEAR Pager package.
 * @see           Original package http://pear.php.net/package/Pager
 * @link          
 */

Class Pager_jumping extends Pager_common
{
    /**
    * Constructor
    *
    * @param array $options Associative array of option 
    *                       names and their values
    * @access public
    */
    function __construct($options = array())
    {
        $res = $this->set_options($options);
        
        if ($res !== TRUE) 
        {
            throw new PagerException('Pager Unknown Error.');
        }
    
        $this->build();
    }
    
    // ------------------------------------------------------------------------

    /**
    * Returns pageID for given offset
    *
    * @param integer $index Offset to get pageID for
    *
    * @return int PageID for given offset
    */
    function get_page_by_offset($index)
    {
        if (!isset($this->_page_data)) 
        {
            $this->_generate_page_data();
        }

        if (($index % $this->_per_page) > 0) 
        {
            $pageID = ceil((float)$index / (float)$this->_per_page);
        } 
        else 
        {
            $pageID = $index / $this->_per_page;
        }
        
        return $pageID;
    }

    // ------------------------------------------------------------------------
    
    /**
     * Given a PageId, it returns the limits of the range of pages displayed.
     * While getOffsetByPageId() returns the offset of the data within the
     * current page, this method returns the offsets of the page numbers interval.
     * E.g., if you have pageId=3 and delta=10, it will return (1, 10).
     * PageID of 8 would give you (1, 10) as well, because 1 <= 8 <= 10.
     * PageID of 11 would give you (11, 20).
     * If the method is called without parameter, pageID is set to currentPage#.
     *
     * @param integer $pageid PageID to get offsets for
     *
     * @return array  First and last offsets
     * @access public
     */
    function get_page_range_by_page($pageid = null)
    {
        $pageid = isset($pageid) ? (int)$pageid : $this->_current_page;
        
        if (isset($this->_page_data[$pageid]) || is_null($this->_item_data)) 
        {
            // I'm sure I'm missing something here, but this formula works
            // so I'm using it until I find something simpler.
            $start = ((($pageid + (($this->_delta - ($pageid % $this->_delta))) % $this->_delta) / $this->_delta) - 1) * $this->_delta +1;
            
            return array(
                max($start, 1),
                min($start+$this->_delta-1, $this->_total_pages)
            );
            
        } 
        else 
        {
            return array(0, 0);
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Returns back/next/first/last and page links,
     * both as ordered and associative array.
     *
     * NB: in original PEAR::Pager this method accepted two parameters,
     * $back_html and $next_html. Now the only parameter accepted is
     * an integer ($pageID), since the html text for prev/next links can
     * be set in the constructor. If a second parameter is provided, then
     * the method act as it previously did. This hack's only purpose is to
     * mantain backward compatibility.
     *
     * @param integer $pageID    Optional pageID. If specified, links for that 
     *                           page are provided instead of current one.
     *                           [ADDED IN NEW PAGER VERSION]
     * @param string  $next_html HTML to put inside the next link
     *                           [deprecated: use the factory instead]
     *
     * @return array Back/pages/next links
     */
    function get_links($pageID = NULL, $next_html = '')
    {
        //BC hack
        if ( ! empty($next_html)) 
        {
            $back_html = $pageID;
            $pageID    = NULL;
        } 
        else 
        {
            $back_html = '';
        }

        if ( ! is_null($pageID)) 
        {
            $this->links = '';
            if ($this->_total_pages > $this->_delta) 
            {
                $this->links .= $this->_print_first_page();
            }

            $_sav = $this->_current_page;
            $this->_current_page = $pageID;

            $this->links .= $this->_get_back_link('', $back_html);
            $this->links .= $this->_get_page_links();
            $this->links .= $this->_get_next_link('', $next_html);
            
            if ($this->_total_pages > $this->_delta) 
            {
                $this->links .= $this->_print_last_page();
            }
        }

        $back        = str_replace('&nbsp;', '', $this->_get_back_link());
        $next        = str_replace('&nbsp;', '', $this->_get_next_link());
        $pages       = $this->_get_page_links();
        $first       = $this->_print_first_page();
        $last        = $this->_print_last_page();
        $all         = $this->links;
        $link_tags   = $this->link_tags;
        $link_tags_raw = $this->link_tags_raw;

        if ( ! is_null($pageID)) 
        {
            $this->_current_page = $_sav;
        }

        return array(
            $back,
            $pages,
            trim($next),
            $first,
            $last,
            $all,
            $link_tags,
            'back'        => $back,
            'pages'       => $pages,
            'next'        => $next,
            'first'       => $first,
            'last'        => $last,
            'all'         => $all,
            'link_tags'   => $link_tags,
            'link_tags_raw' => $link_tags_raw,
        );
    }

    // ------------------------------------------------------------------------

    /**
    * Returns pages link
    *
    * @param string $url URL to use in the link
    *                    [deprecated: use the constructor instead]
    *
    * @return string Links
    * @access private
    */
    function _get_page_links($url = '')
    {
        // legacy setting... the preferred way to set an option now
        // is adding it to the constuctor
        if ( ! empty($url)) 
        {
            $this->_base_url = $url;
        }

        //If there's only one page, don't display links
        if ($this->_clear_if_void AND ($this->_total_pages < 2)) 
        {
            return '';
        }

        $links = '';
        $limits = $this->get_page_range_by_page($this->_current_page);

        for ($i=$limits[0]; $i<=min($limits[1], $this->_total_pages); $i++) 
        {
            if ($i != $this->_current_page) 
            {
                $this->range[$i] = FALSE;
                $this->_link_data[$this->_url_var] = $i;
                $links .= $this->_render_link(str_replace('%d', $i, $this->_alt_page), $i);
            } 
            else 
            {
                $this->range[$i] = TRUE;
                $links .= $this->_cur_page_span_pre . $i . $this->_cur_page_span_post;
            }
            
            $links .= $this->_spaces_before
                   . (($i != $this->_total_pages) ? $this->_separator.$this->_spaces_after : '');
        }
        return $links;
    }

}

// END Pager_jumping Class

/* End of file Pager_jumping.php */
/* Location: ./base/libraries/php5/drivers/pager/Pager_jumping.php */