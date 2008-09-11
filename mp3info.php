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

	function __construct( $file_name, $is_local )
	{
		$this->open( $file_name );
	}

	function open( $file_name )
	{
		$file_handle= FALSE;
		$file_handle = fopen( $file_name, 'rb'  );
		if( $file_handle === FALSE ) {
			return FALSE;
		}

		$pos = 0;
		$frame_bitrate = 0;
		$total_bitrate = 0; // total frames bit rate (used to calc. average)
		$frame_mark = 0xE0;
		$framesize = 0;
		$hdr = NULL;

		$this->content = stream_get_contents( $file_handle );
		$this->size = strlen( $this->content );

		$ch = $this->content[$pos];
		while ( $pos < $this->size ) {
			// first byte of frame header
			if ( ord( $ch ) == 0xFF ) {
				$ch = $this->content[$pos+1];
				// second byte of frame header
				if ( $this->num_frames == 0 && ( ord( $ch ) & $frame_mark ) == $frame_mark ) {
					$hdr = new MP3FrameHeader( substr( $this->content, $pos, 4 ) );
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
					$hdr = new MP3FrameHeader( substr( $this->content, $pos, 4 ) );
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
				$ch = $this->content[$pos];
			}
		}

		// if at least one frame was read, the MP3 is considered valid
		if ( $this->num_frames > 0 ) {
			$this->bitrate = (int)($total_bitrate / $this->num_frames ); // average the bitrate
//			$this->duration = (int)($this->size / ( $this->bitrate / 8 ) );
			$this->duration = ($this->num_frames * $this->samples_per_frame / $this->samplerate);
		}
		else {
			$this->bitrate = 0;
			$this->duration = 0;
		}
		$this->file_name = $file_name;
		fclose( $file_handle );

		return TRUE;
	}

	function format_minutes_seconds( $seconds )
	{
	    $min = (int)$seconds / 60;
	    $sec = $seconds % 60;

		$str = sprintf( "%d:%02d", $min, $sec );

		return $str;
	}

	function get_size()
	{
		return $this->size;
	}

	function get_frame_count()
	{
		return $this->num_frames;
	}

	function get_duration()
	{ 
		return $this->duration;
	}

	function get_mpeg_version()
	{ 
		return $this->mpeg_version;
	}

	function get_mpeg_layer()
	{ 
		return $this->mpeg_layer;
	}

	function has_CRC()
	{ 
		return $this->has_CRC;
	}

	function get_bitrate()
	{ 
		return $this->bitrate;
	}

	function get_samplerate()
	{ 
		return $this->samplerate;
	}

	function get_channel_mode()
	{ 
		return $this->channel_mode;
	}

	function get_emphasis()
	{ 
		return $this->emphasis;
	}

	function is_copyrighted()
	{ 
		return $this->copyrighted;
	}

	function is_original()
	{ 
		return $this->original;
	}

}
?>