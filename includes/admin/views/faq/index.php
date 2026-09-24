<?php
/**
 * Include header for Admin pages
 *
 * @since 8.23.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


?>
<!-- Content Area -->
<div class="container ac-faq-page">
                <div class="row">
                    <div class="col-md-12">
                        <div class="wbc-box">
                            <div class="wbc-head">
                                <h2><?php esc_html_e( 'Frequently Asked Questions', 'woocommerce-ac' ); ?></h2>
                            </div>
							<?php
												$total_count = count( $ts_faq );
												$halfcount = (int ) $total_count/2;
							?>
                            <div class="wbc-content">
                                <div class="ac-faq-wrap">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="ac-wc-accordian">
                                                <div class="panel-group ac-accordian" id="accordion">
												<?php
												$i = 1;
                                                foreach ( $ts_faq as $faq_key => $faq_content ) {
                                                    if( $i <= $halfcount ) {
                                                    ?>
                                                    <!-- First Panel -->
                                                    <div class="panel panel-default  ">
                                                        <div class="panel-heading">
                                                            <h4 class="panel-title" data-toggle="collapse" data-target="#collapse<?php echo wp_kses_post( $i ); ?>" aria-expanded="false">
                                                               <?php echo wp_kses_post( $faq_content['question'] ) ?>
                                                        </div>
                                                        <div id="collapse<?php echo wp_kses_post( $i ); ?>" class="panel-collapse collapse">
                                                            <div class="panel-body">
                                                                 <p><?php echo wp_kses_post( $faq_content['answer'] ) ?></p>
                                                            </div>
                                                        </div>
                                                    </div> 
	<?php $i++;
	}
	}	?>
													
                                                </div>
                                            </div>
                                            </div>
											
											
											<div class="col-md-6">
                                            <div class="ac-wc-accordian">
                                                <div class="panel-group ac-accordian" id="accordion">
												<?php
												$i = 1;
												
    foreach ( $ts_faq as $faq_key => $faq_content) {		
		if( $i >= $halfcount ) {
        ?>
                                                    <!-- First Panel -->
                                                    <div class="panel panel-default  ">
                                                        <div class="panel-heading">
                                                            <h4 class="panel-title" data-toggle="collapse" data-target="#collapse<?php echo wp_kses_post( $i ); ?>" aria-expanded="false">
                                                               <?php echo wp_kses_post( $faq_content['question'] ) ?>
                                                        </div>
                                                        <div id="collapse<?php echo wp_kses_post( $i ); ?>" class="panel-collapse collapse">
                                                            <div class="panel-body">
                                                                 <p><?php echo wp_kses_post( $faq_content['answer'] ) ?></p>
                                                            </div>
                                                        </div>
                                                    </div> 
													
									<?php 

	}
	$i++;
	}

									?>
													
                                                </div>
                                            </div>
                                            </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
		<!-- Content Area End -->
	
	<?php include_once( dirname( __FILE__ ) . '/' . '../ac-footer.php' ); ?>