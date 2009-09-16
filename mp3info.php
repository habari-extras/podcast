<?php

require_once( 'mp3frameheader.php' );

class MP3Info
{

	private $file_name;
	private $size; // size of the file in bytes
	private $validity;
	private $content = '';

	// MP3 frame information. Many of these are not yet used.
	private $num_frames;
	private $duration;	// in seconds
	private $mpeg_version;
	private $mpeg_layer;
	private $has_CRC;
	private $bitrate;	// average
	private $samplerate;
	private $samples_per_frame;
	private $channel_mode;
	private $mode_extension;
	private $emphasis;
	private $is_copyrighted;
	private $is_original;
	private $is_private;

	function __construct( $file_name )
	{
		$this->file_name = $file_name;
	}

	public function open()
	{
		$pos = 0;
		$frame_bitrate = 0;
		$total_bitrate = 0; // total frames bit rate (used to calc. average)
		$frame_mark = 0xE0;
		$framesize = 0;
		$hdr = NULL;
		$content = '';

		$tmp = $this->get_file( $this->file_name );
		if( ! $tmp ) {
			return FALSE;
		}

		$fh = @fopen( $tmp, 'rb' );
		if( ! $fh ) {
			return FALSE;
		}

		$this->size = filesize( $tmp );

		$ch = fgetc( $fh );
		while( $pos < $this->size ) {
			// first byte of frame header
			if ( ord( $ch ) == 0xFF ) {
				$ch = fgetc( $fh );
				// second byte of frame header
				if ( $this->num_frames == 0 && ( ord( $ch ) & $frame_mark ) == $frame_mark ) {
					fseek( $fh, $pos );
					$hdr = new MP3FrameHeader( fread( $fh, 4 ) );
					$frame_bitrate = $hdr->bitrate;
					if( $frame_bitrate != -1 ) {
						$this->num_frames++;
						$frame_mark = ord( $ch );
						$total_bitrate += $frame_bitrate;
						// Get the version
						$this->mpeg_version = $hdr->version;
						// Get the layers
						$this->mpeg_layer = $hdr->layer;
						// Get samples per frame
						$this->samples_per_frame = $hdr->samples_per_frame;
						// Get samplerate
						$this->samplerate = $hdr->samplerate;
						$this->has_CRC = $hdr->has_CRC;
						$this->channel_mode = $hdr->channel_mode;
						$this->mode_extension = $hdr->mode_ext;
						$this->emphasis = $hdr->emphasis;
						$this->is_copyrighted = $hdr->copyright;
						$this->is_original = $hdr->original;
						$this->is_private = $hdr->is_private;
						if( $this->mpeg_layer == MP3FrameHeader::MPEG_LAYER_1 ) {
							$framesize = ( 12 * $frame_bitrate / $this->samplerate + $hdr->is_padded ) * 4;
						}
						else {
							$framesize = 144 * $frame_bitrate / $this->samplerate + $hdr->is_padded;
						}
						$pos += $framesize - 2;
					}
				}
				else if ( ( ord( $ch ) & $frame_mark ) == $frame_mark ) {
					fseek( $fh, $pos );
					$hdr = new MP3FrameHeader( fread( $fh, 4 ) );
					$frame_bitrate = $hdr->bitrate;
					if( $frame_bitrate != -1 ) {
						$this->num_frames++;
						$total_bitrate += $frame_bitrate;

						if( $this->mpeg_layer == MP3FrameHeader::MPEG_LAYER_1 ) {
							$framesize = ( 12 * $frame_bitrate / $this->samplerate + $hdr->is_padded ) * 4;
						}
						else {
							$framesize = 144 * $frame_bitrate / $this->samplerate + $hdr->is_padded;
						}
						$pos += $framesize - 2;
					}
				}
			}
			$pos++;
			if( $pos < $this->size ) {
				fseek( $fh, $pos, SEEK_SET );
				$ch = fgetc( $fh );
			}
		}

		// if at least one frame was read, the MP3 is considered valid
		if ( $this->num_frames > 0 ) {
			$this->bitrate = (int)($total_bitrate / $this->num_frames ); // average the bitrate
			$this->duration = ($this->num_frames * $this->samples_per_frame / $this->samplerate);
		}
		else {
			$this->bitrate = 0;
			$this->duration = 0;
		}

		if( ! $this->is_local_file( $this->file_name ) ) {
			unlink( $tmp );
		}
		return TRUE;
	}

	public function format_minutes_seconds( $seconds )
	{
	    $min = (int)$seconds / 60;
	    $sec = $seconds % 60;

		$str = sprintf( "%d:%02d", $min, $sec );

		return $str;
	}

	public function get_size()
	{
		return $this->size;
	}

	public function get_frame_count()
	{
		return $this->num_frames;
	}

	public function get_duration()
	{ 
		return $this->duration;
	}

	public function get_mpeg_version()
	{ 
		return $this->mpeg_version;
	}

	public function get_mpeg_layer()
	{ 
		return $this->mpeg_layer;
	}

	public function has_CRC()
	{ 
		return $this->has_CRC;
	}

	public function get_bitrate()
	{ 
		return $this->bitrate;
	}

	public function get_samplerate()
	{ 
		return $this->samplerate;
	}

	public function get_channel_mode()
	{ 
		return $this->channel_mode;
	}

	public function get_emphasis()
	{ 
		return $this->emphasis;
	}

	public function is_copyrighted()
	{ 
		return $this->copyrighted;
	}

	public function is_original()
	{ 
		return $this->original;
	}

	protected function get_file( $file_name )
	{
		if( $this->is_local_file( $file_name ) ) {
			EventLog::log('local file');
			return $this->get_local( $file_name );
		}
		else if( ini_get( 'allow_url_fopen' ) ) {
			return $this->get_with_streams( $file_name );
		}
		else if( function_exists( 'curl_init' ) && ! ( ini_get( 'safe_mode' ) && ini_get( 'open_basedir' ) ) ) {
			return $this->get_with_curl( $file_name );
		}
	}

	protected function is_local_file( $file_name = '' )
	{
		$parsed = InputFilter::parse_url( $file_name );
		$parsed_home = InputFilter::parse_url( Site::get_url( 'habari' ) );
		return $parsed['host'] == $parsed_home['host'];
	}

	protected function get_local( $file_name = '' )
	{
		$parsed = InputFilter::parse_url( $file_name );
		return HABARI_PATH . $parsed['path'];
	}

	protected function get_with_streams( $file_name = '' )
	{
		$tmp = tempnam( '/user/cache', 'RR' );
		$temph = fopen( $tmp, 'wb' );
		$fp = fopen( $file_name, 'rb' );
		stream_copy_to_stream( $fp, $temph );
		fclose( $temph );
		fclose( $fp );
		return $tmp;
	}

	protected function get_with_curl( $file_name = '' )
	{
		$headers = array();
		$timeout = 180;
		$method = 'GET';
		$tmp = tempnam( '/user/cache', 'RR' );
		$headers[] = "User-Agent: Habari";
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $file_name ); // The URL.
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 5 ); // Maximum number of redirections to follow.
		curl_setopt( $ch, CURLOPT_CRLF, true ); // Convert UNIX newlines to \r\n.
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); // Follow 302's and the like.
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers ); // headers to send

		$th = fopen( $tmp, 'wb' );
		curl_setopt( $ch, CURLOPT_FILE, $th );

		$success = curl_exec( $ch );
		fclose( $th );
		if( ! $success || curl_errno($ch) != 0 || curl_getinfo( $ch, CURLINFO_HTTP_CODE ) !== 200 ) {
			curl_close( $ch );
			unlink( $tmp );
			return FALSE;
		}
		curl_close( $ch );
		return $tmp;
	}

}
?>