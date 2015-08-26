<?php

class Test_AuthorWordcount extends WP_UnitTestCase {

	public function tearDown() {
		delete_option( 'author_wordcount' );
	}

	/**
	 * @covers WP_Author_Wordcount::__construct
	 */
	public function test_new() {
		$class = new WP_Author_Wordcount();

		$this->assertNotNull( $class );
		$this->assertEquals( array(), $class->word_obj );
	}

}

