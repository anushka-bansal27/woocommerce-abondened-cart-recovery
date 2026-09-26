<?php
/**
 * FAQ & Support page
 */
?>
<style>
    .faq-ts-accordion {
        background-color: #ccc;
        color: #444;
        cursor: pointer;
        padding: 18px;
        width: 100%;
        border: none;
        text-align: left;
        outline: none;
        font-size: 15px;
        transition: 0.4s;
        margin-bottom: 5px;
    }
    .active, .faq-ts-accordion:hover {
        background-color: #ccc; 
    }
    .faq-ts-accordion:after {
        content: '\002B';
        color: #777;
        font-weight: bold;
        float: right;
        margin-left: 5px;
    }
    .active:after {
        content: "\2212";
    }
    .panel {
        padding: 0 18px;
        display: none;
        background-color: light-grey;
        overflow: hidden;
    }
    .main-panel {
        width: 650px !important;
    }
    .support-panel {
        padding: 5px;
    }
    .dashicons-external {
        content: "\f504";
    }
    .dashicons-editor-help {
        content: "\f223";
    }
    div.panel.show {
        display: block !important;
    }

</style>

<div class="main-panel">
    <h3>Frequently Asked Questions for <?php echo esc_html( $ts_plugin_name ); ?> Plugin</h3>
    <?php
    foreach ( $ts_faq as $faq_key => $faq_content) {
        ?>
        <button class="faq-ts-accordion"><span class="dashicons dashicons-editor-help"></span><strong><?php echo wp_kses_post( $faq_content['question'] ) ?></strong></button>
        <div class="panel">
            <p><?php echo wp_kses_post( $faq_content['answer'] ) ?></p>
        </div>
    <?php
    }
    ?>
</div>

<div class="support-panel">
    <p style="font-size: 19px">
        If your queries are not answered here, you can send get in touch with us <a href="https://support.tychesoftwares.com/help/2285384554" target="_blank">here</a> for some additional requirements. 
    </p>
</div>
<script>
var acc = document.getElementsByClassName("faq-ts-accordion");
var i;

for (i = 0; i < acc.length; i++) {
    acc[i].onclick = function() {
        hideAll();

        this.classList.toggle("active");
        this.nextElementSibling.classList.toggle("show");
    }
}

function hideAll() {
    for (i = 0; i < acc.length; i++) {
        acc[i].classList.toggle( "active", false);
        acc[i].nextElementSibling.classList.toggle( "show", false );
    }
}

</script>