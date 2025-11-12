@extends('templates.inside')
@section('content')

 <!-- Page Header Section Start Here -->
        <section class="page-header style-2">
            <div class="container">
                <div class="page-title text-center">
                    <h3>Fraud Prevention</h3>
                    <ul class="breadcrumb">
                        <li><a href="{{ URL::to('/') }}">Home</a></li>
                        <li>Fraud Prevention</li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- Page Header Section Ending Here -->

        
        <!-- About Section Start here -->
		<section class="about about-page padding-tb">
            <div class="container">
                <div class="row align-items-center">
                    
                    <div class="col-lg-12 col-12">
                        <div class="about-content">
                            
                            <div class="section-wrapper">

                                <h6>Our Commitment to Protection</h6>
                                <p>At Pahatud we take protecting our customers' sensitive information seriously and are constantly monitoring our information and data to prevent fraudulent or suspicious behavior.</p>

                                <p>We strive to protect you and encourage you to be aware of any potential fraudulent behavior by malicious parties using the Pahatud brand. Below are some tips for how you can recognize fraud to ensure that sensitive information is kept secure.</p>

                                <p><strong>Recognize Fraud</strong> Recognizing phishing and scam e-mails and other types of communications is key to protecting yourself against fraud and theft. Common warning signs of online scams include:</p>

                                
                                <p><strong>Requests for Money:</strong> Unexpected extra requests for money, often with a sense of urgency, and used to trick people into sending money and provide personal information such as usernames, passwords, and credit card details. Requests for Personal Information: Requests for personal and/or financial information for the purpose of committing theft, identify theft and other crimes.</p>

                                <p><strong>Deceptive Domain Names:</strong> Links to misspelled or slightly altered website addresses (e.g. Pahatod.com / Pahatod.org / bills-request). If you doubt the integrity of any website using the Pahatud brand, please visit our global website: www.Pahatud.com or contact Pahatud customer service. Types of Fraud</p>

                                
                                <p><strong>Fraudulent Emails and Email Scams:</strong> These are the most common online scams. Such emails attempt to trick you by pretending to come from a reputable source, such as from pahatud (for example from what appears to be an pahatud email). They will try to get you to share sensitive personal, account information or send a payment. They may also ask you to enroll in winning a prize or entering a competition.</p>

                                <p>We urge customers to be suspicious of any request not coming directly from an pahatud employee or domain name.</p>

                                
                                <p><strong>Identity Theft:</strong> This occurs when someone tricks you into disclosing/providing personal, financial or account information. Posing as well-known companies, information thieves usually send out e-mails or contact you by telephone asking you to reply or direct you to a fraudulent web page that asks you to provide personal information, such as your credit card number or account password, or even your pahatud credentials.</p>

                                <p><strong>Credit Card Fraud:</strong> In some instances, credit card fraud occurs when someone's physical credit card is lost or stolen by another party who uses it. Credit card fraud is driven primarily by the compromise of credit card account data during their normal course of usage. Stolen credit card data is often used to attempt fraudulent online purchases.</p>

                                <p><strong>Spam and Viruses:</strong> In our industry, customers usually receive an email suggesting that pahatud is attempting to deliver a package and request that you open the email attachment to prompt the delivery. This attachment may be a computer virus. Unless you are expecting to receive an email like this, please do not open the attachment and report it to support at (support@pahatud.com).</p>

                                <p><strong>Package Tracking Emails:</strong> In some cases, customers receive an email containing a tracking number. This number can be verified by inserting it into the “Track Shipment” box on www.pahatud.com. If there are no tracking results returned, it is not a valid tracking number, and pahatud did not send the email. Please report this email to pahatud support at (support@pahatud.com).</p>

                                <h6>Doing Our Part</h6>
                                <p>The security of your personal information is important to us. At Pahatud, we recognize industry standards and employs appropriate administrative, technical and physical security safeguards to protect the personal information you provide against accidental, unlawful or unauthorized destruction, loss, alteration, access, disclosure or use and other unlawful forms of processing. We continually invest in latest world-class technologies to minimize all potential risks for the benefit of our customers, and we remain committed to ensuring we meet the most stringent security requirements and fostering a security culture within our organization.</p>

                                <h6>Doing Your Part</h6>
                                <p>Pahatud will keep these measures under review and enhance them from time to time as necessary. It is important for you to protect against unauthorized access to your login information, including your password. Please note that no method of transmission over the Internet, or method of electronic storage, is 100% secure. Therefore, Pahatud cannot guarantee the absolute security of your personal information.</p>

                                <h6>Contact Us</h6>
                                <p>For any comments or questions on Pahatud Customer Protection and Fraud Prevention Measures, please contact Pahatud Support at (support@Pahatud.com).</p>

                                <h6>Disclaimer:</h6>
                                <p>Pahatud does not, and will not request you to provide any personal or payment information through traditional mail or via email. Being aware and protecting your sensitive information is the best way to prevent fraud. If you receive a request for personal or payment information through these types of communications, please do not reply or cooperate with the sender and immediately report the case to Support at (support@Pahatud.com).</p>

                                <p>Pahatud accepts no responsibility for any costs, charges or payments made as a result of fraudulent activity. It is important to be mindful of the above ways your information and data can be stolen to prevent any fraud in the future.</p>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
		<!-- About Section Ending here -->
		
        
         @include('pages.includes.sponsor')

        

       @include('pages.includes.newsletter')


@endsection