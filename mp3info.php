<?php

class MP3Info
{
	// MPEG version constants
	const MPEG_VER_NA = 3;
	const MPEG_VER_25 = 2;
	const MPEG_VER_2 = 1;
	const MPEG_VER_1 = 0;

	const MPEG_LAYER_NA = 3;
	const MPEG_LAYER_3 = 2;
	const MPEG_LAYER_2 = 1;
	const MPEG_LAYER_1 = 0;

	// matrix of bitrates [based on MPEG version data][bitrate index]
	private $mp3_bitrate = array(
		array(0,32,64,96,128,160,192,224,256,288,320,352,384,416,448,-1), // MPEG-1, Layer 1
		array(0,32,48,56, 64, 80, 96,112,128,160,192,224,256,320,384,-1), // MPEG-1, Layer 2
		array(0,32,40,48, 56, 64, 80, 96,112,128,160,192,224,256,320,-1), // MPEG-1, Layer 3
		array(0,32,48,56, 64, 80, 96,112,128,144,160,176,192,224,256,-1), // MPEG-2,2.5, Layer 1
		array(0, 8,16,24, 32, 40, 48, 56, 64, 80, 96,112,128,144,160,-1), // MPEG-2,2.5, Layer 2
		array(0, 8,16,24, 32, 64, 80, 56, 64,128,160,112,128,256,320,-1), // MPEG-2,2.5, Layer 3
	);

	private $mp3_samples_per_frame = array(
		array( 0, 384, 1152, 1152 ), //MPEG-1 Layer 1 - 2- 3
		array( 0, 384, 1152, 576 ), // MPEG-2 Layer 1 - 2 - 3
		array( 0, 384, 1152, 576 ), // MPEG-2.5 Layer 1 - 2 - 3
	);

	private $mp3_samplerate = array(
		array( 44100, 22050, 11025 ), // Layer 1 - 2 - 3
		array( 48000, 24000, 12000 ), // Layer 1 - 2 - 3
		array( 32000, 16000, 8000 ), // Layer 1 - 2 - 3
		array( -1, -1, -1 ), // Layer 1 - 2 - 3
	);

	private $file_name;
	private $size; // size of the file in bytes
	private $validity;
	private $content = '';

	// MP3 frame information. Many of these are not yet used.
	private $num_frames;
	private  $duration;	// in seconds
	private  $mpeg_version;
	private  $mpeg_layer;
	private  $has_CRC;
	private  $bitrate;	// average if VBR, 0 if "free"
	private  $samplerate;
	private  $samples_per_frame;
	private  $channel_mode;
	private  $emphasis;
	private  $is_copyrighted;
	private  $is_original;

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

		$this->content = stream_get_contents( $file_handle );
		$this->size = strlen( $this->content );

		$ch = $this->content[$pos];
		while ( $pos < $this->size ) {
			// first byte of frame header
			if ( ord( $ch ) == 0xFF ) {
				$pos++;
				$ch = $this->content[$pos];
				$pos++;

				// second byte of frame header
				if ( $this->num_frames == 0 && ( ord( $ch ) & $frame_mark ) == $frame_mark ) {
					$frame_bitrate = $this->read_bitrate( $file_handle, $pos );
					if( $frame_bitrate != -1 ) {
						$this->num_frames++;
						$frame_mark = ord( $ch );
						$total_bitrate += $frame_bitrate;
						// Get the version
						$v = ( ord( $ch ) & 0x18 ) >> 3;
						switch ( $v ) {
							case 0:
								$this->mpeg_version = self::MPEG_VER_25;
								break;
							case 1:
								$this->mpeg_version = self::MPEG_VER_NA;
								break;
							case 2:
								$this->mpeg_version = self::MPEG_VER_2;
								break;
							case 3:
								$this->mpeg_version = self::MPEG_VER_1;
								break;
						}
						// Get the layers
						$l = ( ord( $ch ) & 0x06 ) >> 1;
						switch( $l ) {
							case 0:
								$this->mpeg_layer = self::MPEG_LAYER_NA;
								break;
							case 1:
								$this->mpeg_layer = self::MPEG_LAYER_3;
								break;
							case 2:
								$this->mpeg_layer = self::MPEG_LAYER_2;
								break;
							case 3:
								$this->mpeg_layer = self::MPEG_LAYER_1;
								break;
						}
						// Get samples per frame
						$this->samples_per_frame = $this->mp3_samples_per_frame[$this->mpeg_version][$this->mpeg_layer];
						// Get samplerate
						$pos++;
						$s = ( ord( $this->content[$pos] ) & 0x0C ) >> 2;
						$this->samplerate = $this->mp3_samplerate[$s][$this->mpeg_version];

						$pad = ( ord( $this->content[$pos] ) & 0x02 ) >> 1;
						if( $this->mpeg_layer == self::MPEG_LAYER_1 ) {
							$framesize = ( 12 * $frame_bitrate / $this->samplerate + $pad ) * 4;
						}
						else {
							$framesize = 144 * $frame_bitrate / $this->samplerate + $pad;
						}
						$pos += $framesize - 5;
					}
				}
				else if ( ( ord( $ch ) & $frame_mark ) == $frame_mark ) {
					$frame_bitrate = $this->read_bitrate( $file_handle, $pos );
					if( $frame_bitrate != -1 ) {
						$this->num_frames++;
						$total_bitrate += $frame_bitrate;

						$pad = ( ord( $ch ) & 0x02 ) >> 1;
						if( $this->mpeg_layer == self::MPEG_LAYER_1 ) {
							$framesize = ( 12 * $frame_bitrate / $this->samplerate + $pad ) * 4;
						}
						else {
							$framesize = 144 * $frame_bitrate / $this->samplerate + $pad;
						}
						$pos += $framesize - 5;
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
//			$this->bitrate = (int)($total_bitrate / $this->num_frames ); // average the bitrate
//			$this->duration = (int)($this->size / ( $this->bitrate / 8 ) );
			$this->duration = (int)($this->num_frames * $this->samples_per_frame / $this->samplerate);
		}
		else {
			$this->bitrate = 0;
		}
		$this->file_name = $file_name;
		fclose( $file_handle );

		return TRUE;
	}

	private function read_bitrate( $handle, $pos )
	{
		$info = ( ord( $this->content[$pos] ) & 0xF0) >> 4;
		$bitrate = -1;

		// read the bitrate, based on the mpeg layer and version
		if ( $this->mpeg_layer != self::MPEG_VER_NA ) {
			if ( $this->mpeg_version == self::MPEG_VER_1 ) {
				switch ( $this->mpeg_layer) {
					case self::MPEG_LAYER_1:
						$bitrate = $this->mp3_bitrate[0][$info];
						break;
					case self::MPEG_LAYER_2:
						$bitrate = $this->mp3_bitrate[1][$info];
						break;
					case self::MPEG_LAYER_3:
						$bitrate = $this->mp3_bitrate[2][$info];
						break;
				}
			}
			else {
				switch ( $this->mpeg_layer ) {
					case self::MPEG_LAYER_1:
						$bitrate = $this->mp3_bitrate[3][$info];
						break;
					case self::MPEG_LAYER_2:
						$bitrate = $this->mp3_bitrate[4][$info];
						break;
					case self::MPEG_LAYER_3:
						$bitrate = $this->mp3_bitrate[5][$info];
						break;
				}
			}
		}

		if( $bitrate != -1) {
			return $bitrate * 1000;
		}
		else {
			return $bitrate;
		}
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