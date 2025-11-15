<?php
$content = <<<TPL
					<!-- page:help -->
					<div id="help" class="common_section">
						<h4 class="text-center vgblue">How can we help you?</h4>
						<div class="container">
							<div class="row">
								<div class="col-2"></div>
								<div class="col-8">
									<form action="{$this->b}/help/search" method="post">
										<input type="hidden" name="token" value="">
										<div class="input-group mb-3">
											<input type="text" name="q" id="q" class="form-control" placeholder="..." aria-label="Search terms" aria-describedby="qtrg">
											<div class="input-group-append">
												<button class="btn btn-primary" type="button" id="qtrg">Search</button>
											</div>
										</div>
									</form>
								</div>
								<div class="col-2"></div>
							</div>
						</div>
						<p>🔹 What is Vergabo?</p>
						<p>Vergabo is a digital platform where manufacturers and procurement professionals can post sourcing requests and receive competitive offers from qualified suppliers — for components, assemblies, or complete solutions.</p>
						<p>🔹 How does it work?</p>
						<p>You submit a request describing what you need — from a single part to a complex unit.</p>
						<p>Suppliers receive your request and reply with their commercial offers.</p>
						<p>You review and compare the offers, then contact the selected supplier directly to proceed.</p>
						<p>All transactions (pricing, shipping, payment) are conducted directly between buyer and supplier — Vergabo acts solely as a neutral intermediary.</p>
						<p>🔹 Who can use Vergabo?</p>
						<p>Manufacturers and OEMs</p>
						<p>Engineering teams</p>
						<p>Procurement specialists</p>
						<p>System integrators</p>
						<p>Maintenance and service departments</p>
						<p>In short: anyone sourcing industrial components, equipment, or related services.</p>
						<p>🔹 What are the benefits?</p>
						<p>A single request reaches multiple suppliers at once.</p>
						<p>Access to suppliers across Europe, the USA, the Gulf region, and beyond.</p>
						<p>Efficient for custom parts, spare parts, small series, and complex sourcing.</p>
						<p>No commissions, no hidden markups — transparent by design.</p>
						<p>🔹 How much does it cost?</p>
						<p>For buyers: Always free — you can post unlimited requests at no charge.</p>
						<p>For suppliers: We offer a fixed subscription model. No per-request fees or percentage-based commissions.</p>
						<p>🔹 How do I submit a request?</p>
						<p>Go to vergabo.com/request, list the components or services you need,  technical details if needed, and click Submit.</p>
						<p>After moderation, your request will be visible to relevant suppliers and published in the open “Requests” section.</p>
						<p>🔹 How do I receive offers?</p>
						<p>Suppliers respond through a secure form with their commercial quotation. You can review all incoming offers, compare terms, and contact the supplier you prefer.</p>
						<p>🔹 Is my contact information visible?</p>
						<p>No. Your contact details are hidden by default and only shared with a supplier after you confirm their offer or choose to initiate contact.</p>
						<p>🔹 How do you protect against fraud?</p>
						<p>All suppliers go through a verification process before gaining access to the platform.</p>
						<p>We also encourage buyers to:</p>
						<p>Use only official business email addresses</p>
						<p>Request supplier credentials and documentation</p>
						<p>Avoid payments to personal or third-party accounts</p>
						<p>Vergabo is not responsible for financial or logistical agreements made outside the platform.</p>
						<p>🔹 What products can be requested?</p>
						<p>Any industrial product or solution, including:</p>
						<p>Automatisation equipment and spare parts</p>
						<p>Gearboxes, actuators, motors</p>
						<p>Pneumatic and hydraulic components</p>
						<p>Automation modules and assemblies</p>
						<p>Custom mechanical systems and parts</p>
						<p>🔹 Export controls, sanctions, and restricted regions</p>
						<p>Vergabo does not control or monitor the shipment or use of goods ordered via the platform.</p>
						<p>Buyers and suppliers are fully responsible for ensuring compliance with all applicable export regulations, international sanctions, and dual-use restrictions.</p>
						<p>This includes but is not limited to:</p>
						<p>Compliance with EU, US, and UN sanctions</p>
						<p>Avoiding transactions involving sanctioned countries, entities, or individuals</p>
						<p>Proper handling and documentation of dual-use goods or controlled technologies</p>
						<p>⚠️ We strongly advise all users to conduct internal due diligence and consult legal or compliance departments before engaging in international transactions. Vergabo disclaims any liability for violations of export or customs law.</p>
						<p>🔹 Which regions are supported?</p>
						<p>Vergabo is a global platform. While our supplier base is focused on Europe, the USA, Eastern Europe, and the Gulf region, we accept requests from buyers around the world.</p>
						<p>Shipping terms and availability depend on the supplier.</p>
						<p>🔹 Why aren’t prices listed?</p>
						<p>Industrial components are rarely standardized — they vary by specification, quantity, lead time, material, and origin.</p>
						<p>Because of this variability, we use a quote-based model.</p>
						<p>🔹 Why do I need to provide a Tax ID Number (TIN)?</p>
						<p>Vergabo requires a valid Tax Identification Number (TIN) during registration to ensure compliance with international trade laws, invoicing standards, and supplier verification procedures.</p>
						<p>Depending on your country, this may be:</p>
						<p>EIN (Employer Identification Number) – USA</p>
						<p>VAT ID or Steuernummer – EU countries</p>
						<p>ИНН – Russia</p>
						<p>CUIT, RUC, or other national tax identifiers</p>
						<p>We do not display your tax number publicly, but it is essential for:</p>
						<p>Validating your company profile</p>
						<p>Confirming the legal and operational status of your business</p>
						<p>Supporting international due diligence and fraud prevention</p>
						<p>❗️If you are a private individual, or if your company does not have (or has an expired) tax number, registration is unfortunately not possible.</p>
						<p>Vergabo is intended exclusively for verified, active legal entities. The Tax ID helps us identify participants as legitimate business partners.</p>
						<p>If you believe your case is an exception, please contact our support team for further clarification.</p>
						<p>🔹 Can my company act as both a buyer and a supplier?</p>
						<p>Yes, absolutely. Many companies on Vergabo participate in both roles — placing sourcing requests as buyers, and responding to opportunities as suppliers.</p>
						<p>However, please note:</p>
						<p>You will need to create two separate accounts — one for each role.</p>
						<p>The buyer account is always free, with unlimited access to posting requests.</p>
						<p>The supplier account allows you to respond to RFQs. After participating in your first 10 listings, you will be asked to activate a subscription plan to continue receiving and sending offers.</p>
						<p>This structure helps us keep the platform fair and sustainable, while allowing maximum flexibility for dual-role companies.</p>
					</div>
					<!-- /page:help -->
TPL;
