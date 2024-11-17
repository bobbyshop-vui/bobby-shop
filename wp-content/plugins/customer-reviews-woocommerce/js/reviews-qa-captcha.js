if (typeof grecaptcha !== 'undefined' && grecaptcha && jQuery('.cr-recaptcha').length) {
	grecaptcha.ready(() => {
		grecaptcha.render(jQuery('.cr-recaptcha')[0], {
			sitekey: crReviewsQaCaptchaConfig.v2Sitekey
		});
	});
}
