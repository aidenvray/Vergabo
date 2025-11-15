(function() {
	'use strict';
	//
	var tmMX = null;
	var tmPC = null;
	//
	async function getMXRecord(domain){
		try {
			const response = await fetch(`https://dns.google/resolve?name=${domain}&type=MX`);
			const data = await response.json();
			if (data && data.Answer && data.Answer.length > 0) {
				const mxRecords = data.Answer.map(record => `${record.data}`).join('\n');
				return mxRecords;
			} else
				return 'no-mx';
		} catch (error) {
			return 'MX-Error';
		}
	}
	//
	async function getLocationByPostalCode(country,code){
		try {
			const response = await fetch(`https://api.zippopotam.us/${country}/${code}`);
			const data = await response.json();
			if (data && data.places && data.places.length > 0) {
				const places = [];
				for(const p of data.places)
					places.push(p['place name']);
				return places.join(', ');
			} else
				return '';
		} catch (error) {
			return '';
		}
	}
	//
	function setTogglePassword(key){
		const togglePassword = document.querySelector('#show_'+key);
		const password = document.querySelector('#'+key);
		togglePassword.addEventListener('click',function(){
			const type = password.getAttribute('type') === 'password' ?  'text' : 'password';
			password.setAttribute('type',type);
			this.classList.toggle('bi-eye');
		});
	}
	// default
	window.addEventListener('load',function(){
        if($('#apwd').length){
			if($('#recovery').length || $('#change_password').length || $('#register').length){
				$('#apwd').strengthMeter('progressBar',{container:$('.pw-strength-bar')});
				setTogglePassword('apwd');
				if($('#apwy').length)
					setTogglePassword('apwy');
				$('#apwd').on('input',function(e){
					let i = e.currentTarget;
					if(8 > i.value.length)
						i.setCustomValidity('Password too short');
					else if(!/[A-Z]/.test(i.value))
						i.setCustomValidity('Password must have at least one capital letter');
					else if(!/\d/.test(i.value))
						i.setCustomValidity('Password must have at least one digit');
					else
						i.setCustomValidity('');
				});
			}
			if($('#register').length){
				$('input[name=postal_code]').on('input',async function(e){
					const alpha2 = $('select[name=country]').val();
					if(alpha2){
						if(tmPC)
							clearTimeout(tmPC);
						tmPC = setTimeout(async function(){
							const loc = await getLocationByPostalCode(alpha2.toLowerCase(),$('input[name=postal_code]').val());
							if(loc)
								$('input[name=city]').val(loc);
						},1000);
					}
				});
				$('input[name=email]').on('input',async function(e){
					this.setCustomValidity('');
					if(!this.validity.valid)
						return;
					//
					if(!/^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]+?)+\.[a-z]{2,63}$/i.test(this.value))
						this.setCustomValidity('Invalid email');
					else {
						if(tmMX)
							clearTimeout(tmMX);
						var anchor = this;
						tmMX = setTimeout(async function(){
							const fqdn = anchor.value.substring(anchor.value.indexOf('@')+1);
							const mxr = await getMXRecord(fqdn);
							console.log('MX:',mxr);
							if('no-mx' == mxr || 'MX-Error' == mxr)
								anchor.setCustomValidity('Invalid email');
						},1000);
					}
				});
			}
		}
		const forms = document.getElementsByClassName('needs-validation');
		const validation = Array.prototype.filter.call(forms,function(form){
			form.addEventListener('submit',function(event){
				if(false === form.checkValidity()){
					event.preventDefault();
					event.stopPropagation();
				}
				form.classList.add('was-validated');
			},false);
		});
		$('[data-toggle="popover"]').popover({html:true});
		$('[data-toggle="tooltip"]').tooltip();
	},false);
})();
