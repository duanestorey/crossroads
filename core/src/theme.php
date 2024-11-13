<?php
/*
    All code copyright (c) 2024 by Duane Storey - All rights reserved
    You may use, distribute and modify this code under the terms of GPL version 3.0 license or later
*/
 
namespace CR;

class Theme {
    protected $themeConfig = null;
    protected $childThemeConfig = null;

    protected $themeName = null;
    protected $parentThemeName = null;

    protected $coreThemeDir = null;
    protected $localThemeDir = null;
    protected $isChildTheme = false;
    protected $isLocalTheme = false;

    protected $primaryThemeDir = false;

    public function __construct( $themeName, $coreThemeDir, $localThemeDir ) {
        $this->themeName = $themeName;
        $this->coreThemeDir = $coreThemeDir;
        $this->localThemeDir = $localThemeDir;
    }

    public function name() {
        return $this->themeName;
    }

    public function isChildTheme() {
        return $this->isChildTheme;
    }

    public function isLocalTheme() {
        return $this->isLocalTheme;
    }

    public function getChildThemeName() {
        return $this->themeName;
    }

    public function getParentThemeName() {
        return $this->parentThemeName;
    }

    public function primaryThemeDir() {
        return $this->primaryThemeDir;
    }

    public function load() {
        // check for local child theme
        if ( file_exists( $this->localThemeDir . '/'. $this->themeName . '/theme.yaml' ) ) {
            $localThemeConfig = new Config( YAML::parse_file( $this->localThemeDir . '/' . $this->themeName . '/theme.yaml', true ) );
            if ( $localThemeConfig->get( 'theme.parent', false ) ) {
                // it has a parent
                $parentTheme = $localThemeConfig->get( 'theme.parent' );

                if ( file_exists( $this->coreThemeDir . '/' . $parentTheme . '/theme.yaml' ) && file_exists( $this->coreThemeDir . '/' . $parentTheme . '/index.latte' ) ) {
                    $this->isChildTheme = true;
                    $this->childThemeConfig = $localThemeConfig;
                    $this->parentThemeName = $parentTheme;
                    $this->isLocalTheme = true;
                    $this->primaryThemeDir = $this->coreThemeDir . '/' . $parentTheme;

                    $this->themeConfig = new Config( YAML::parse_file( $this->coreThemeDir . '/' .  $parentTheme . '/theme.yaml', true ) );

                    return true;
                }
            } else {
                // not a child theme, but may be a regular theme
                if ( file_exists( $this->localThemeDir . '/' . $this->themeName . '/index.latte' ) && file_exists( $this->localThemeDir . '/' . $this->themeName . '/index.latte' ) ) {
                    $this->themeConfig = new Config( YAML::parse_file( $this->localThemeDir . '/' .  $this->themeName . '/theme.yaml', true ) );
                    $this->isLocalTheme = true;
                    $this->primaryThemeDir = $this->localThemeDir . '/' . $this->themeName;

                    return true;
                }
            }   
        }

        if ( file_exists( $this->coreThemeDir . '/' . $this->themeName . '/theme.yaml' ) && file_exists( $this->coreThemeDir . '/' . $this->themeName . '/index.latte' ) ) {
            $this->themeConfig = new Config( YAML::parse_file( $this->coreThemeDir . '/' .  $this->themeName . '/theme.yaml', true ) );
            $this->primaryThemeDir = $this->coreThemeDir . '/' . $this->themeName;

            return true;
        }

        return false;
    }

    public function getAssetHash() {
        $hashValue = 0;

        foreach( $this->themeConfig->get( 'theme.assets', [] ) as $destName => $sources ) {
            if ( file_exists( CROSSROADS_PUBLIC_DIR . '/assets/' . $destName ) ) {
                $hashValue = $hashValue . filesize( CROSSROADS_PUBLIC_DIR . '/assets/' . $destName );
            }
        }

        return md5( $hashValue );
    }

    protected function accumulateAssets( $contentSoFar, $actualFile ) {
        if ( file_exists( $actualFile ) ) {
            if ( SASS::isSassFile( $actualFile ) ) {
                $contentSoFar = $contentSoFar . "\n\n" . SASS::parseFile( $actualFile );
                LOG( sprintf( _i18n( 'core.class.theme.adding' ), $actualFile ), 3, LOG::DEBUG );
            } else {
                $contentSoFar = $contentSoFar . "\n\n" . file_get_contents( $actualFile );
                LOG( sprintf( _i18n( 'core.class.theme.sass' ), $actualFile ), 3, LOG::DEBUG );
            }
        } else {
            LOG( sprintf( _i18n( 'core.class.theme.no_source' ), $actualFile ), 3, LOG::WARNING );
        }

        return $contentSoFar;
    }

    public function processAssets() {
        foreach( $this->themeConfig->get( 'theme.assets', [] ) as $destName => $sources ) {
            LOG( sprintf( _i18n( 'core.class.theme.processing' ), $destName ), 2, LOG::INFO );

            $content = '';
            foreach( $sources as $key => $source ) {
                $actualFile = $this->primaryThemeDir . '/assets/' . $source;
                $content = $this->accumulateAssets( $content, $actualFile );
            }

            LOG( "Writing static file [" . $destName . "]", 4, LOG::DEBUG );

            file_put_contents( CROSSROADS_PUBLIC_DIR . '/assets/' . $destName, $content );
        }

        foreach( $this->themeConfig->get( 'theme.images', '[]' ) as $imageFile ) {
            if ( file_exists( $this->coreThemeDir . '/assets/' . $imageFile ) ) {
                Utils::copyFile( $this->coreThemeDir . '/assets/' . $imageFile, CROSSROADS_PUBLIC_DIR . '/assets/' . pathinfo( $imageFile, PATHINFO_BASENAME ) );
                LOG( sprintf( _i18n( 'core.class.theme.copying' ), $imageFile, CROSSROADS_PUBLIC_DIR . '/assets/' . pathinfo( $imageFile, PATHINFO_BASENAME ) ), 3, LOG::DEBUG );
            }
        }
    }
}