 <!-- Newsletter Section Start Here -->
<div class="news-letter">
    <div class="container">
        <div class="section-wrapper">
            <div class="news-title">
                <h3>For Newsletter</h3>
            </div>
            <div class="news-form">
                <form action="{{ route('newsletter.submit') }}" method="post">
                    @csrf
                    <label>
                        <input type="email" name="email" placeholder="Enter Your Email">
                    </label>
                    <input type="submit" name="submit" value="Subscribe now">
                </form>
                
            </div>
        </div>
    </div>
</div>
<!-- Newsletter Section Ending Here -->