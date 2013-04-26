<?php
	function DIMEPadding($DataLength) // If we added "abcdef", add "00" to get to the next group of 4
	{
		$result = '';
		for ( $Pad = $DataLength ; $Pad & 3 ; $Pad++ ) $result = $result."\0";
		return $result;
	}
	
	function ToDIME(
		$Flags,   // 0, 1, or 2
		$ID,   // Blank, or a GUID in hexadecimal encoding
		$IDLength,
		$Type, // "text/xml" or a URI to an XML specification
		$TypeLength,
		$Body,  // The XML fragment we're wrapping
		$BodyLength)    // How long it is
	{
		// Format lengths into the bytes of the DIME header
		
		$result = chr(0x08 | ( $Flags & 1 ? 4 : 0 ) | ( $Flags & 2 ? 2 : 0 ));
		$result = $result.chr(strchr( $Type, ':' ) ? 0x20 : 0x10);
		$result = $result.chr(0x00);
		$result = $result.chr(0x00);
		$result = $result.chr( ( $IDLength & 0xFF00 ) >> 8 );
		$result = $result.chr( $IDLength & 0xFF );
		$result = $result.chr( ( $TypeLength & 0xFF00 ) >> 8 );
		$result = $result.chr( $TypeLength & 0xFF );
		$result = $result.chr( ( $BodyLength & 0xFF000000 ) >> 24 );
		$result = $result.chr( ( $BodyLength & 0x00FF0000 ) >> 16 );
		$result = $result.chr( ( $BodyLength & 0x0000FF00 ) >> 8 );
		$result = $result.chr( $BodyLength & 0x000000FF );

		// Print $ID, which is blank or a GUID in hexadecimal encoding, and bytes of 0 until the total length we added is a multiple of 4
		$result = $result.$ID.DIMEPadding($IDLength);
		 // If we added "a", add "000" to get to the next group of 4

		// Print $Type, which is "text/xml" or a URI to an XML specification, and bytes of 0 until the total length we added is a multiple of 4
		$result = $result.$Type.DIMEPadding($TypeLength);

		// If there is body text
		if ( $Body != NULL )
		{
			// Add it, followed by bytes of 0 until the total length we added is a multiple of 4
			$result = $result.$Body.DIMEPadding($BodyLength);
		}
		return $result;
	}
?>