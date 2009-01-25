<?php

class MP3FrameHeader
{

	/* A frame header is a 32 bit value with the following layout
	* Bits 0 - 10: frame sync;
	* Bits 11 - 12: mpeg version
	* Bits 13 - 14: number of layers
	* Bit 15: whether the frame has a crc value
	* Bits 16 - 19: bitrate index
	* Bits 20 - 21: samplerate index
	* Bit 22: padding
	* Bit 23: whether the file is private
	* Bits 24 - 25: channel mode
	* Bits 26 - 27: channel mode extension
	* Bit 28: whether the file is copyrighted
	* Bit 29: whether the file is original
	* Bits 30 - 31: emphasis
	*
	**/

	// MPEG version constants
	const MPEG_VER_NA = 3;
	const MPEG_VER_25 = 2;
	const MPEG_VER_2 = 1;
	const MPEG_VER_1 = 0;

	// MPEG layer constants
	const MPEG_LAYER_NA = 3;
	const MPEG_LAYER_3 = 2;
	const MPEG_LAYER_2 = 1;
	const MPEG_LAYER_1 = 0;

	// Channel mode constants
	const MPEG_MODE_STEREO = 0;
	const MPEG_MODE_JOINT_STEREO = 1;
	const MPEG_MODE_DUAL_CHANNEL = 2;
	const MPEG_MODE_SINGLE_CHANNEL = 3;

	// Emphasis constants
	const MPEG_EM_NONE = 0;
	const MPEG_EM_50_15_MS = 1;
	const MPEG_EM_RESERVED = 2;
	const MPEG_EM_CCIT_J17 = 3;

	// matrix of bitrates [based on MPEG version data][bitrate index]
	private $mp3_bitrate = array(
		array(0,32,64,96,128,160,192,224,256,288,320,352,384,416,448,-1), // MPEG-1, Layer 1
		array(0,32,48,56, 64, 80, 96,112,128,160,192,224,256,320,384,-1), // MPEG-1, Layer 2
		array(0,32,40,48, 56, 64, 80, 96,112,128,160,192,224,256,320,-1), // MPEG-1, Layer 3
		array(0,32,48,56, 64, 80, 96,112,128,144,160,176,192,224,256,-1), // MPEG-2,2.5, Layer 1
		array(0, 8,16,24, 32, 40, 48, 56, 64, 80, 96,112,128,144,160,-1), // MPEG-2,2.5, Layer 2
		array(0, 8,16,24, 32, 64, 80, 56, 64,128,160,112,128,256,320,-1), // MPEG-2,2.5, Layer 3
	);

	// matrix of samples per frame by version and layer
	private $mp3_samples_per_frame = array(
		array( 0, 384, 1152, 1152 ), //MPEG-1 Layer 1 - 2- 3
		array( 0, 384, 1152, 576 ), // MPEG-2 Layer 1 - 2 - 3
		array( 0, 384, 1152, 576 ), // MPEG-2.5 Layer 1 - 2 - 3
	);

	// matrix of samplerates by index and version
	private $mp3_samplerate = array(
		array( 44100, 22050, 11025 ), // Version 1 - 2 - 2.5
		array( 48000, 24000, 12000 ), // Version 1 - 2 - 2.5
		array( 32000, 16000, 8000 ), // Version 1 - 2 - 2.5
		array( -1, -1, -1 ), // Version 1 - 2 - 2.5
	);

	private $version_index;
	private $layer_index;
	public $has_CRC;
	private $bitrate_index;
	private $samplerate_index;
	public $is_padded;
	public $is_private;
	public $channel_mode;
	public $mode_extension;
	public $copyright;
	public $original;
	public $emphasis;

	function __construct( $value )
	{
		$this->version_index = ( ord( $value[1] ) & 0x18 ) >> 3;
		$this->layer_index = ( ord( $value[1] ) & 0x06 ) >> 1;
		$this->has_CRC = ord( $value[1] ) & 0x01;
		$this->bitrate_index = ( ord( $value[2] ) & 0xF0 ) >> 4;
		$this->samplerate_index = ( ord( $value[2] ) & 0x0C ) >> 2;
		$this->is_padded = ( ord( $value[2] ) & 0x02 ) >> 1;
		$this->is_private = ord( $value[2] ) & 0x01;
		$this->channel_mode = ( ord( $value[3] ) & 0xC0 ) >> 6;
		$this->mode_extension = ( ord( $value[3] ) & 0x30 ) >> 4;
		$this->copyright = ( ord( $value[3] ) & 0x08 ) >> 3;
		$this->original = ( ord( $value[3] ) & 0x04 ) >> 2;
		$this->emphasis = ord( $value[3] ) & 0x03;

	}

	private function get_bitrate()
	{
		$bitrate = -1;
		// read the bitrate, based on the mpeg layer and version
		if ( $this->layer != self::MPEG_VER_NA ) {
			if ( $this->version == self::MPEG_VER_1 ) {
				switch ( $this->layer) {
					case self::MPEG_LAYER_1:
						$bitrate = $this->mp3_bitrate[0][$this->bitrate_index];
						break;
					case self::MPEG_LAYER_2:
						$bitrate = $this->mp3_bitrate[1][$this->bitrate_index];
						break;
					case self::MPEG_LAYER_3:
						$bitrate = $this->mp3_bitrate[2][$this->bitrate_index];
						break;
				}
			}
			else {
				switch ( $this->layer ) {
					case self::MPEG_LAYER_1:
						$bitrate = $this->mp3_bitrate[3][$this->bitrate_index];
						break;
					case self::MPEG_LAYER_2:
						$bitrate = $this->mp3_bitrate[4][$this->bitrate_index];
						break;
					case self::MPEG_LAYER_3:
						$bitrate = $this->mp3_bitrate[5][$this->bitrate_index];
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

	private function get_samplerate()
	{
		return $this->mp3_samplerate[$this->samplerate_index][$this->version];
	}

	private function get_samples_per_frame()
	{
		return $this->mp3_samples_per_frame[$this->version][$this->layer];
	}

	function __get( $prop )
	{
		switch ( $prop ) {
			case 'bitrate':
				return $this->get_bitrate();
				break;
			case 'samplerate':
				return $this->get_samplerate();
				break;
			case 'samples_per_frame':
				return $this->get_samples_per_frame();
				break;
			case 'version':
				switch( $this->version_index ) {
					case 0:
						return self::MPEG_VER_25;
						break;
					case 1:
						return self::MPEG_VER_NA;
						break;
					case 2:
						return self::MPEG_VER_2;
						break;
					case 3:
						return self::MPEG_VER_1;
						break;
				}
				break;
			case 'layer':
				switch( $this->layer_index ) {
					case 0:
						return self::MPEG_LAYER_NA;
						break;
					case 1:
						return self::MPEG_LAYER_3;
						break;
					case 2:
						return self::MPEG_LAYER_2;
						break;
					case 3:
						return self::MPEG_LAYER_1;
						break;
				}
				break;
		}
	}
}
?>