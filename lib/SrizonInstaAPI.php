<?php
if ( ! class_exists( 'SrizonInstaAPI' ) ) {
	class SrizonInstaAPI{
		static $prefix = 'https://api.instagram.com/v1/';

		static function getCacheOrAPI( $endpoint, $album_id, $count, $direct_link = false ) {
			$url = $endpoint;
			if ( ! $direct_link ) {
				$url = self::buildURL( $endpoint, $count );
			}
			$cache = SrizonInstaDB::getAPICache( $url );
			if ( $cache ) {
				$response            = $cache->data;
				$response->storetime = $cache->storetime;

				return $response;
			} else {
				$response = self::getAPI( $url, true );
				if ( ! is_wp_error( $response ) ) {
					SrizonInstaDB::updateAPICache( $url, $album_id, $response );
				}
				$response->storetime = time();

				return $response;
			}
		}

		static function getAPI( $endpoint, $direct_link = false, $count = 20 ) {
			$url = $endpoint;
			if ( ! $direct_link ) {
				$url = self::buildURL( $endpoint, $count );
			}

			$response = wp_remote_get( $url, [ 'timeout' => 30 ] );
			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'Getting Data Failed', $response->get_error_message(), [ 'status' => 500 ] );
			} else {
				return json_decode( $response['body'] );
			}
		}

		static function buildURL( $endpoint, $count ) {
			$token      = '?access_token=' . get_option( 'srizon_instagram_access_token', false );
			$countParam = '&count=' . $count;

			return self::$prefix . $endpoint . $token . $countParam;
		}

		static function getAlbumLoadMore( $id, $url ) {
			return self::getCacheOrAPI( $url, $id, 0, true );
		}

		static function getAlbumData( $id ) {
			$album_opt = SrizonInstaDB::getAlbum( $id );
			$count     = 20;
			if ( $album_opt->options['layout'] == 'collage' ) {
				$count = $album_opt->options['initial_load'];
			}
			if ( $album_opt->options['layout'] == 'carousel' ) {
				$count = $album_opt->options['total_image_carousel'];
			}
			if ( $album_opt->albumtype == 'user' ) {
				return self::getUserAlbumData( $album_opt->userid, $id, $count );
			} else if ( $album_opt->albumtype == 'hashtag' ) {
				return self::getHashtagAlbumData( $album_opt->hashtag, $id, $count );
			} else {
				return new WP_Error( 'wrong_album_type', 'Wrong Album Type', [ 'status' => 404 ] );
			}
		}

		static function syncAlbum( $id ) {
			$albumdata = self::getAlbumData( $id );
			$album_opt = SrizonInstaDB::getAlbum( $id );
			$cachetime = 60 * (int) $album_opt->options['cache_time'];
			$timediff  = time() - $albumdata->storetime;

			if ( $timediff > $cachetime ) {
				SrizonInstaDB::DeleteAlbumCache( $id );
				$fresh_albumdata = self::getAlbumData( $id );

				return $fresh_albumdata;
			}

			return false;
		}

		static function getUserAlbumData( $userid, $id, $count = 20 ) {
			$data = self::getCacheOrAPI( 'users/' . $userid . '/media/recent', $id, $count );

			return $data;
		}

		static function getHashtagAlbumData( $hashtag, $id, $count = 20 ) {
			return self::getCacheOrAPI( 'tags/' . $hashtag . '/media/recent', $id, $count );
		}
	}
}