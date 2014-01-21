<?php
namespace Loco\Http\Response;

use Guzzle\Service\Command\ResponseClassInterface;
use Guzzle\Service\Command\OperationCommand;
use Guzzle\Http\Message\Response;


/**
 * responseClass for endpoints that return binary zip files
 */
class ZipResponse extends RawResponse implements ResponseClassInterface {
    
    /**
     * @var \ZipArchive
     */
    private $zip;
    

    /**
     * @return ZipResponse
     */
    public static function fromCommand( OperationCommand $command ) {
        $response = $command->getResponse();
        $me = new self;
        return $me->init( $response );
    }
    
    
    /**
     * Get zip archive instance
     * @return \ZipArchive
     */
    public function getZip(){
        if( ! $this->zip ){
            // temporary file required for opening zip
            $tmp = tempnam( sys_get_temp_dir(), 'loco_zip_' );
            register_shutdown_function( 'unlink', $tmp );
            file_put_contents( $tmp, $this->__toString() );
            $this->zip = new \ZipArchive;
            if( ! $this->zip->open( $tmp, \ZipArchive::CHECKCONS ) ){
                throw new \Exception('Failed to open zip archive from response data');
            }
        }
        return $this->zip;
    }   
     

}