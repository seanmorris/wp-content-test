<?php

class WXZ_Parser {
	function parse( $file ) {
		require_once ABSPATH . '/wp-admin/includes/class-pclzip.php';

		$base_url      = false;
		$base_blog_url = false;
		$authors       = array();
		$posts         = array();
		$terms         = array();
		$categories    = array();
		$objects       = array();

		$archive = new PclZip( $file );
		$archive_files = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING );

		$mimetype_exists = false;
		foreach ( $archive_files as $file ) {
			if ( 'mimetype' === $file['filename'] ) {
				if ( 'application/vnd.wordpress.export+zip' === trim( $file['content'] ) ) {
					$mimetype_exists = true;
				}
				break;
			}
		}

		if ( ! $mimetype_exists ) {
			return new WP_Error( 'invalid-file', 'Invalid WXZ field, mimetype declaration missing.' );
		}

		foreach ( $archive_files as $file ) {
			if ( $file['folder'] ) {
				continue;
			}

			$type = dirname( $file['filename'] );
			$name = basename( $file['filename'], '.json' );
			$item = json_decode( $file['content'], true );

			if ( 'site' === $type && 'config' === $name ) {
				if ( isset( $item['link'])) {
					$base_url = $item['link'];
				}
				continue;
			}

			$id = intval( $name );
			if ( 'users' === $type ) {
				$author = array(
					'author_id'           => (int) $id,
					'author_login'        => (string) $item['username'],
					'author_display_name' => (string) $item['display_name'],
					'author_email'        => (string) $item['email'],
				);

				$authors[] = $author;
				continue;
			}

			if ( 'posts' === $type ) {
				$post = array(
					'post_id'        => (int) $id,
					'post_title'     => (string) $item['title'],
					'post_content'   => (string) $item['content'],
					'post_type'      => (string) $item['type'],
					'guid'           => (string) $item['guid'],
					'status'         => (string) $item['status'],
					'post_parent'    => (string) $item['parent'],
					'post_name'      => (string) $item['slug'],
					'post_excerpt'   => (string) $item['excerpt'],
					'post_status'    => (string) $item['status'],
					'post_date'      => (string) $item['date_utc'],
					'post_date_gmt'  => (string) $item['date_utc'],
					'post_author'    => (string) $item['author'],
					'post_password'  => (string) $item['password'],
					'comment_status' => (string) $item['comment_status'],
					'ping_status'    => (string) $item['ping_status'],
					'menu_order'     => (string) $item['menu_order'],
					'attachment_url' => (string) $item['attachment_url'],
					'postmeta'       => (string) $item['postmeta'],
				);

				$posts[] = $post;
				continue;
			}

			if ( 'terms' === $type ) {
				$term = array(
					'term_id'          => (int) $id,
					'term_taxonomy'    => (string) $item['taxonomy'],
					'slug'             => (string) $item['slug'],
					'term_parent'      => (string) $item['parent'],
					'term_name'        => (string) $item['name'],
					'term_description' => (string) $item['description'],
				);

				$terms[] = $term;
				continue;
			}

			if ( 'categories' === $type ) {
				$category = array(
					'term_id'              => (int) $id,
					'category_nicename'    => (string) $item['name'],
					'category_parent'      => (string) $item['parent'],
					'cat_name'             => (string) $item['slug'],
					'category_description' => (string) $item['description'],
				);

				$categories[] = $category;
				continue;
			}

			if ( 'objects' === $type ) {
				$object = array(
					'object_id' => (int) $id,
					'type'      => (string) $item['type'],
					'data'      => $item['data'],
				);

				$objects[] = $object;
				continue;
			}
		}

		return array(
			'authors'       => $authors,
			'posts'         => $posts,
			'categories'    => $categories,
			'terms'         => $terms,
			'base_url'      => $base_url,
			'base_blog_url' => $base_blog_url,
		);
	}
}
