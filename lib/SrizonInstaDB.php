<?php

class SrizonInstaDB{
	static function CreateDBTables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$t_albums = $wpdb->prefix . 'srzinst_albums';
		$t_cache  = $wpdb->prefix . 'srzinst_cache';
		$sql      = '
CREATE TABLE ' . $t_albums . ' (
  id int(11) NOT NULL AUTO_INCREMENT,
  title text,
  albumtype varchar(255),
  userid varchar(255),
  username varchar(255),
  full_name varchar(255),
  profile_picture text,
  hashtag varchar(255),
  options text,
  PRIMARY KEY (id)
) '.$charset_collate.';
CREATE TABLE ' . $t_cache . ' (
  id int(11) NOT NULL AUTO_INCREMENT,
  url varchar(511),
  data mediumtext,
  storetime int(11),
  album_id int(11),
  options text,
  index(url),
  PRIMARY KEY (id)
) '.$charset_collate.';	
';
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	static function SaveAlbum( $payload ) {
		global $wpdb;
		$table                   = $wpdb->prefix . 'srzinst_albums';
		$data['title']           = $payload['title'];
		$data['albumtype']       = $payload['type'];
		$data['userid']          = $payload['userid'];
		$data['username']        = $payload['username'];
		$data['full_name']       = $payload['full_name'];
		$data['profile_picture'] = $payload['profile_picture'];
		$data['hashtag']         = $payload['hashtag'];
		$data['options']         = $payload['options'];

		$wpdb->insert( $table, $data );

		return $wpdb->insert_id;
	}

	static function UpdateAlbumSettings( $id, $payload ) {
		global $wpdb;
		$table = $wpdb->prefix . 'srzinst_albums';

		$data['title']   = $payload->title;
		$data['options'] = maybe_serialize( $payload );

		$wpdb->update( $table, $data, [ 'id' => $id ] );
	}

	static function DeleteAlbum( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'srzinst_albums';
		$q     = $wpdb->prepare( "delete from $table where id = %d", $id );
		$wpdb->query( $q );
		self::DeleteAlbumCache( $id );
	}

	static function DeleteAlbumCache( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'srzinst_cache';
		$q     = $wpdb->prepare( "delete from $table where album_id = %d", $id );
		$wpdb->query( $q );
	}

	static function getAlbum( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'srzinst_albums';
		$q     = $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );
		$album = $wpdb->get_row( $q );
		if ( $album ) {
			$album->options = array_merge( srizon_instagram_album_global_defaults(), (array) maybe_unserialize( $album->options ) );
		}

		return $album;
	}

	static function GetAllAlbums() {
		global $wpdb;
		$table  = $wpdb->prefix . 'srzinst_albums';
		$albums = $wpdb->get_results( "SELECT * FROM $table order by id desc" );
		foreach ( $albums as $album ) {
			$album->options = array_merge( srizon_instagram_album_global_defaults(), (array) maybe_unserialize( $album->options ) );
		}

		return $albums;
	}

	static function getAPICache( $url ) {
		global $wpdb;
		$table = $wpdb->prefix . 'srzinst_cache';
		$q     = $wpdb->prepare( "SELECT * FROM $table WHERE url = '%s'", $url );
		$data  = $wpdb->get_row( $q );
		if ( $data ) {
			$data->data = unserialize( $data->data );

			return $data;
		}

		return false;
	}

	static function updateAPICache( $url, $album_id, $data ) {
		global $wpdb;
		$wpdb->show_errors();
		$table = $wpdb->prefix . 'srzinst_cache';

		$tdata['album_id']  = $album_id;
		$tdata['data']      = maybe_serialize( $data );
		$tdata['storetime'] = time();

		$res = $wpdb->update( $table, $tdata, [ 'url' => $url ] );
		if ( ! $res ) {
			$tdata['url'] = $url;
			$wpdb->insert( $table, $tdata );
		}
	}
}