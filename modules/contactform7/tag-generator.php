<?php $args = wp_parse_args( $args, array() ); ?>

<div class="control-box">
    <fieldset>
        <legend>Generate a form-tag for various Infusionsoft fields.<br/><br/> <b>Note:</b> You need to insert at least <b>First Name</b> and <b>Email</b> in order to send the form's data to Infusionsoft!</legend>

        <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="infusionsoft-field">Field Type</label></th>
                <td>
                    <select name="infusionsoft-field" class="tg-name oneline" id="infusionsoft-field">
                        <option value="0">-</option>
                        <?php
                            foreach ($this->inf_fields as $value => $name) {
                                echo '<option value="' . $value . '">' . $name . '</option>';
                            }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'contact-form-7' ) ); ?></label></th>
                <td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
                    <label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'contact-form-7' ) ); ?></label></td>
                </tr>

                <tr>
                    <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
                    <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
                    <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
                            <label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
                        </fieldset>
                    </td>
                </tr>
        </tbody>
        </table>
    </fieldset>
</div>

<div class="insert-box" style="height: 100px; bottom: 50px; overflow-x: hidden;">
    <input type="text" name="text" class="tag code" readonly="readonly" onfocus="this.select()" />

    <div class="submitbox">
        <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
    </div>

    <br class="clear" />

    <p class="description mail-tag">
        <label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>">
            <?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?>
            <input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" />
        </label>
    </p>
</div>