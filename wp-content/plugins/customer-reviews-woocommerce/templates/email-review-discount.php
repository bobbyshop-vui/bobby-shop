<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="padding:0;">
		<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" style="background-color:#F3F2F1;margin:0;padding-top:70px;padding-bottom:10px;padding-left:0;padding-right:0;-webkit-text-size-adjust:none !important;width:100%;">
			<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
				<tr>
					<td align="center" valign="top">
						<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container" style="box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1) !important;background-color:#FFFFFF;border: 1px solid #dcd9d6;border-radius: 3px !important;">
							<tr>
								<td align="center" valign="top">
									<!-- Header -->
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" style="background-color:<?php echo $cr_email_color_bg; ?>;border-radius: 3px 3px 0 0 !important;color:<?php echo $cr_email_color_text; ?>;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:Helvetica,Roboto,Arial,sans-serif;">
										<tr>
											<td id="header_wrapper" style="padding:36px 48px;display:block;width:100%;box-sizing:border-box;">
												<h1 style="color:<?php echo $cr_email_color_text; ?>;line-height:150%;background-color:inherit;"><?php echo $cr_email_heading; ?></h1>
											</td>
										</tr>
									</table>
									<!-- End Header -->
								</td>
							</tr>
							<tr>
								<td align="center" valign="top">
									<!-- Body -->
									<table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body">
										<tr>
											<td valign="top" id="body_content" style="background-color:#FFFFFF;">
												<!-- Content -->
												<table border="0" cellpadding="20" cellspacing="0" width="100%">
													<tr>
														<td valign="top" style="padding:48px 48px 32px;">
															<div id="body_content_inner" style="color:#605952;font-family:Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:<?php echo is_rtl() ? 'right' : 'left'; ?>;">
																<?php echo wpautop( wp_kses_post( $cr_email_body ) ); ?>
															</div>
														</td>
													</tr>
												</table>
												<!-- End Content -->
											</td>
										</tr>
									</table>
									<!-- End Body -->
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td align="center" valign="top">
						<!-- Footer -->
						<table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer" style="padding:0;border-radius:6px;">
							<tr>
								<td valign="top">
									<table border="0" cellpadding="10" cellspacing="0" width="100%">
										<tr>
											<td colspan="2" valign="middle" id="credit" style="border:0;color:#958c83;font-family:Helvetica,Roboto,Arial,sans-serif;font-size:12px;line-height:150%;text-align:center;padding:12px 0;">
												<?php echo wp_kses_post( wpautop( wptexturize( $cr_email_footer ) ) ); ?>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<!-- End Footer -->
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>
