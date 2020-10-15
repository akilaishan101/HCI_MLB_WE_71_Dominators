<?php

/**
 * @package WP Encryption
 *
 * @author     Go Web Smarty
 * @copyright  Copyright (C) 2019-2020, Go Web Smarty
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://gowebsmarty.com
 * @since      Class available since Release 5.1.0
 *
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
class WPLE_Trait
{
    /**
     * Progress & error indicator
     *
     * @since 4.4.0 
     * @return void
     */
    public static function wple_progress_bar()
    {
        $stage1 = $stage2 = $stage3 = $stage4 = '';
        $progress = get_option( 'wple_error' );
        
        if ( FALSE === $progress ) {
            //still waiting first run
        } else {
            
            if ( $progress == 0 ) {
                //success
                $stage1 = $stage2 = $stage3 = 'prog-1';
            } else {
                
                if ( $progress == 1 || $progress == 400 || $progress == 429 ) {
                    //failed on first step
                    $stage1 = 'prog-0';
                } else {
                    
                    if ( $progress == 2 ) {
                        $stage1 = 'prog-1';
                        $stage2 = 'prog-0';
                    } else {
                        
                        if ( $progress == 3 ) {
                            $stage1 = $stage2 = 'prog-1';
                            $stage3 = 'prog-0';
                        } else {
                            
                            if ( $progress == 4 ) {
                                $stage1 = $stage2 = $stage3 = 'prog-1';
                                $stage4 = 'prog-0';
                            } else {
                                if ( $progress == 5 ) {
                                    $stage1 = $stage2 = $stage3 = 'prog-1';
                                }
                            }
                        
                        }
                    
                    }
                
                }
            
            }
        
        }
        
        if ( FALSE !== ($cmp = get_option( 'wple_complete' )) && $cmp ) {
            $stage1 = $stage2 = $stage3 = $stage4 = 'prog-1';
        }
        $out = '<ul class="wple-progress">
      <li class="' . $stage1 . '"><a href="?page=wp_encryption&restart=1" class="wple-tooltip" data-tippy="' . esc_attr__( "Click to re-start from beginning", 'wp-letsencrypt-ssl' ) . '"><span>1</span>&nbsp;' . esc_html__( 'Registration', 'wp-letsencrypt-ssl' ) . '</a></li>
      <li class="' . $stage2 . '"><span>2</span>&nbsp;' . esc_html__( 'Domain Verification', 'wp-letsencrypt-ssl' ) . '</li>
      <li class="' . $stage3 . '"><span>3</span>&nbsp;' . esc_html__( 'Certificate Generated', 'wp-letsencrypt-ssl' ) . '</li>
      <li class="' . $stage4 . '"><span>4</span>&nbsp;' . esc_html__( 'Install Certificate', 'wp-letsencrypt-ssl' ) . '</li>';
        $out .= '</ul>';
        return $out;
    }
    
    public static function wple_get_acmename( $nonwwwdomain, $identifier )
    {
        $dmn = $nonwwwdomain;
        
        if ( FALSE !== ($slashpos = stripos( $dmn, '/' )) ) {
            $pdomain = substr( $dmn, 0, $slashpos );
        } else {
            $pdomain = $dmn;
        }
        
        $parts = explode( '.', $dmn );
        $subdomain = '';
        $acmedomain = str_ireplace( $pdomain, '', $identifier );
        if ( count( $parts ) > 2 && strlen( $parts[0] ) >= 3 ) {
            $subdomain = $parts[0] . '.';
        }
        
        if ( count( $parts ) > 3 ) {
            //double nested subdomain
            $subdomain = '';
            $acmedomain = $identifier;
        }
        
        $acme = '_acme-challenge.' . esc_html( $acmedomain ) . $subdomain;
        if ( count( $parts ) <= 3 ) {
            $acme = substr( $acme, 0, -1 );
        }
        return $acme;
    }

}