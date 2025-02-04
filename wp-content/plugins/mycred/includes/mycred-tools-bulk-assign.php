<?php
if( !class_exists( 'myCRED_Tools_Bulk_Assign' ) ):
class myCRED_Tools_Bulk_Assign extends myCRED_Tools
{

    private static $_instance;

    public static function get_instance()
    {
        if (self::$_instance == null)
            self::$_instance = new self();

        return self::$_instance;
    }

    public function __construct()
    {
        add_action( 'wp_ajax_mycred-tools-assign-award', array( $this, 'tools_assign_award' ) );
    }

    public function get_header()
    {
        // $bulk_point = get_mycred_tools_page_url( 'bulk-assign', 'points' );
        $bulk_badge = get_mycred_tools_page_url( 'bulk-assign', 'badges' );
        $bulk_rank = get_mycred_tools_page_url( 'bulk-assign', 'ranks' );
       
        
        $page = isset( $_GET['bulk-assign'] ) ? sanitize_text_field( wp_unslash( $_GET['bulk-assign'] ) ) : sanitize_text_field( wp_unslash( $_GET['page'] ) );

        ?>
        
        <div id="mycred-tools-pagination" class="pagination-links">
            
            <a href="<?php echo esc_url( admin_url('admin.php?page=mycred-tools') ); ?>" class="<?php echo ( isset( $_GET['page'] ) && $_GET['page'] == 'mycred-tools' && ! isset( $_GET['bulk-assign'] ) ) ? 'mycred-tools-selected' : 'button'; ?>"><?php esc_html_e( 'Points','mycred' ); ?></a>
            
            <?php
            if( class_exists( 'myCRED_Badge' ) )
            {
                $current = ( isset( $_GET['bulk-assign'] ) && $_GET['bulk-assign'] == 'badges' ) ? 'mycred-tools-selected' : 'button';
                echo ' <a href="' . esc_url( $bulk_badge ) . '" class="' . esc_attr( $current ) . '"> Badges</a>';
            }

            if( class_exists( 'myCRED_Ranks_Module' ) )
            {
                $current = ( isset( $_GET['bulk-assign'] ) && $_GET['bulk-assign'] == 'ranks' ) ? 'mycred-tools-selected' : 'button';
                echo ' <a href="' . esc_url( $bulk_rank ) . '" class="' . esc_attr( $current ) . '">Ranks</a>';
            }
            ?>
            
            <input type="hidden" class="request-tab" value="<?php echo isset( $_GET['bulk-assign'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['bulk-assign'] ) ) ) : esc_attr( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ); ?>" />


        </div>
        <br class="clear">
        <?php

        $this->get_page( $page );
    }

    public function get_page( $page )
    {

        $point_types = mycred_get_types();

        $pt_args = array(
            'name'  => 'bulk_award_pt', 
            'id'    =>  'bulk-award-pt', 
            'class' =>  'bulk-award-pt'
        );

        $user_args = array(
            'users' =>  array(
                'name'  =>  'bulk_users',
                'class' =>  'bulk-users',
                'id'    =>  'bulk-users'
            ),
            'roles' =>  array(
                'name'  =>  'bulk_roles',
                'class' =>  'bulk-roles',
                'id'    =>  'bulk-roles'
            ),
        );

        //Badges
        $badges_args = array(
            'name'      =>  'bulk_badges', 
            'id'        =>  'bulk-badges', 
            'class'     =>  'bulk-badges',
            'multiple'  =>  'multiple'
        );

        $badges = array();
        if (class_exists('myCRED_Badge')){
            
            $badge_ids = mycred_get_badge_ids();

            foreach( $badge_ids as $id )
                $badges[$id] = get_the_title( $id );
        }

        //Ranks
        $ranks_args = array(
            'name'      =>  'bulk_ranks', 
            'id'        =>  'bulk-ranks', 
            'class'     =>  'bulk-ranks'
        );

        $ranks = array();

        foreach( $point_types as $key => $pt )
        {
            $mycred_ranks = '';
            
            if( class_exists( 'myCRED_Ranks_Module' ) && mycred_manual_ranks( $key ) )
            {
                $mycred_ranks = mycred_get_ranks( 'publish', '-1', 'ASC', $key );

                foreach( $mycred_ranks as $key => $value )
                {
                    $ranks[$value->post->ID] = "{$value->post->post_title} ({$pt})";
                }
            }
        }

        ?>
        <form class="mycred-tools-ba-award-form form">
            <table width="" class="mycred-tools-ba-award-table" cellpadding="10">
                <?php 
                if ( $page == 'mycred-tools' ) { ?>
                <tbody class="bulk-award-point">
                    <tr>
                        <td><label for="bulk-points"><?php esc_html_e( 'Points to Award/ Deduct', 'mycred' ) ?></label></td>
                        <td>
                            <input id="bulk-points" type="number" name="bulk_award_point" class="form-control">
                        </td>
                    </tr>

                    <tr>
                        <td class="tb-zero-padding"></td>
                        <td class="tb-zero-padding">
                            <p><i>
                                <?php esc_html_e( 'Either set points are Positive to award or in Negative to deduct.', 'mycred' ); ?>
                            </i></p>
                            <p><i>
                                <?php esc_html_e( 'eg. 10 or -100 ', 'mycred' ); ?>
                            </i></p>
                        </td>
                    </tr>

                    <tr>
                        <td><label for="bulk-award-pt"><?php esc_html_e( 'Select Point Type', 'mycred' ) ?></label></td>
                        <td>
                            <?php 

                            $select_field = mycred_create_select_field($point_types, array(), $pt_args);
                            if ($select_field === null) {
                                $select_field = ''; 
                            }
                            echo wp_kses(
                                $select_field,
                                array(
                                    'select' => array(
                                        'id' => array(),
                                        'name' => array(),
                                        'class' => array(),
                                        'style' => array()
                                    ),
                                    'option' => array(
                                        'value' => array(),
                                        'selected' => array()
                                    ),
                                )
                            );

                            ?>              
                        </td>
                    </tr>

                    <tr>
                        <td><label for="bulk-check-log"><?php esc_html_e( 'Enable to Log Entry', 'mycred' ) ?></label></td>
                        <td>
                            <label class="mycred-toggle">
                                <input id="bulk-check-log" type="checkbox" value="1" class="log-entry">
                                <span class="slider round"></span>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <td class="tb-zero-padding"></td>
                        <td class="tb-zero-padding">
                            <p><i>
                                <?php esc_html_e( 'Check if you want to create log of this entry.', 'mycred' ) ?>
                            </i></p>
                        </td>
                    </tr>

                    <tr class="log-entry-row">
                        <td><label for="bulk-log-entry"><?php esc_html_e( 'Log Entry', 'mycred' ) ?></label></td>
                        <td>
                            <input id="bulk-log-entry" type="text" name="log_entry_text" class="form-control">
                            <p><i>
                                <?php esc_html_e( 'Enter Text for log entry.', 'mycred' ) ?>
                            </i></p>
                        </td>
                    </tr>
                    
                </tbody>
                <?php }
                if( $page =='badges' ) { ?>
                <tbody class="bulk-award-badge">
                    <tr>
                        <td><label for="bulk-badges"><?php esc_html_e( 'Select Badge(s)', 'mycred' ) ?></label></td>
                        <td>
                            <?php 
                            $select2_field = mycred_create_select2($badges, $badges_args);
                            if ($select2_field === null) {
                                $select2_field = ''; 
                            }

                            echo wp_kses(
                                $select2_field,
                                array(
                                    'select' => array(
                                        'id' => array(),
                                        'name' => array(),
                                        'class' => array(),
                                        'style' => array(),
                                        'multiple' => array()
                                    ),
                                    'option' => array(
                                        'value' => array(),
                                        'selected' => array()
                                    ),
                                )
                            );
                            ?>

                        </td>
                    </tr>
                </tbody>
                <?php } 
                if ( $page == 'ranks' ) { ?>
                <tbody class="bulk-award-rank">
                    <tr>
                        <td><label for="bulk-ranks"><?php esc_html_e( 'Select Rank', 'mycred' ) ?></label></td>
                        <td>
                          <?php 
                            $select_field = mycred_create_select_field($ranks, array(), $ranks_args);
                            if ($select_field === null) {
                                $select_field = ''; // Handle null by setting a default value
                            }

                            echo wp_kses(
                                $select_field,
                                array(
                                    'select' => array(
                                        'id' => array(),
                                        'name' => array(),
                                        'class' => array(),
                                        'style' => array()
                                    ),
                                    'option' => array(
                                        'value' => array(),
                                        'selected' => array()
                                    ),
                                )
                            );
                            ?>

                        </td>
                    </tr>
                    <tr class="bulk-award-rank">
                        <td class="tb-zero-padding"></td>
                        <td class="tb-zero-padding">
                            <p>
                                <i>Rank Behaviour should be set to Manual Mode.</i>
                            </p>
                        </td>
                    </tr>
                </tbody>
                <?php }
                // User fields
                $allowed_html = array(
                    'tbody' => array(),
                    'p'     => array(),
                    'i'     => array(),
                    'tr'    => array(
                        'class'     => array()
                    ),
                    'td'    => array(
                        'class'     => array()
                    ),
                    'label' => array(
                        'class'     => array(),
                        'for'       => array()
                    ),
                    'input' => array(
                        'type'      => array(),
                        'value'     => array(),
                        'name'      => array(),
                        'class'     => array(),
                        'id'        => array(),
                        'checked'   => array()
                    ),
                    'span'  => array(
                        'class'     => array()
                    ),
                    'select' => array(
                        'id'        => array(),
                        'style'     => array(),
                        'name'      => array(),
                        'class'     => array(),
                        'multiple'   => array()
                    ),
                    'option' => array(
                        'value'     => array(),
                        'selected'  => array()
                    )
                ); 
                
                echo wp_kses( $this->users_fields( $user_args ), $allowed_html );?>

                <!-- Award Button -->
                <tbody>
                    <div class="popup" id="myPopup">
                        <div class="popup" id="myPopup">
                            <div class="wrapper" aria-labelledby="popupTitle" aria-describedby="popupText" aria-modal="true">
                                <span class="close">×</span>
                                <h3>Please wait !!!</h3>
                                <div class="myCred_tool_loader"></div>
                                <br>
                                <span id="myCred_users">Users : </span>
                                <br>
                                <br>
                                <span id="myCred_user_remaining">User Remaining : </span>
                            </div>
                        </div>
                    </div>
                <tr>
                    <td colspan="2">
                        <button class="button button-primary tools-bulk-assign-award-btn award-points" >Update</button>
                        <?php
                        $badge_page = isset( $_GET['bulk-assign'] ) ? $_GET['bulk-assign'] : '';
                        if ( 'badges' == $badge_page ) {
                            ?>
                            <button class="button button-primary tools-revoke-btn">Revoke</button>
                            <?php
                        }
                        ?> 
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <?php
    }

    public function users_fields( $args )
    {
        $users = array();
        
        $users_args = array(
            'name'      =>  $args['users']['name'],
            'id'        =>  $args['users']['id'],
            'class'     =>  $args['users']['class'],
            'multiple'  =>  'multiple'
        );

        $wp_roles = wp_roles();

        $roles = array();

        foreach( $wp_roles->roles as $role => $name )
        {
            $roles[$role] = $name['name'];
        }

        $roles_args = array(
            'name'      =>  $args['roles']['name'],
            'id'        =>  $args['roles']['id'],
            'class'     =>  $args['roles']['class'],
            'multiple'  =>  'multiple'
        );

        $content = '';
        
        $content .= 
        '<tr>
            <td><label for="bulk-reward-all-users">Award/ Revoke to All Users</label></td>
            <td>
                <label class="mycred-toggle">
                    <input id="bulk-reward-all-users" type="checkbox" name="" class="award-to-all">
                    <span class="slider round"></span>
                </label>
            </td>
        </tr>

        <tr class="users-row">
            <td class="tb-zero-padding">
            </td>
            <td class="tb-zero-padding">
                <p><i>
                    Check if you want to award to all users.
                </i></p>
            </td>
        </tr>
        
        <tr class="users-row">
            <td><label for="bulk-users">Users to Award/ Revoke</label></td>
            <td>';

        $content .= mycred_create_select2( $users, $users_args );

        $content .='
            </td>
        </tr>

        <tr class="users-row">
            <td class="tb-zero-padding">
            </td>
            <td class="tb-zero-padding">
            <p><i>
                Choose users to award.
            </i></p>
            </td>
        </tr>
        
        <tr class="users-row">
            <td><label for="bulk-roles">Roles to Award/ Revoke</label></td>
            <td>';

        $content .= mycred_create_select2( $roles, $roles_args );

        $content .= '
            </td>
        </tr>
        <tr class="users-row">
            <td class="tb-zero-padding">
            </td>
            <td class="tb-zero-padding">
                <p><i>
                    Choose roles to award.
                </i></p>
            </td>
        </tr>
        ';

        return $content;
    }
}
endif;

myCRED_Tools_Bulk_Assign::get_instance();