<template>
     <section class="product style-2 padding-tb" style="background-image: url(assets/css/bg-image/product-2.jpg);">
            <div class="container">
                <div class="section-header">
                    <h3>{{ restaurant.restaurant_name }}</h3>
                    <p>{{ restaurant.address }}</p>
                    <p>{{ restaurant.food_type.title }}</p>
                </div>
                
                <div class="section-wrapper">
                    <ul class="tab-bar">
                        <li class="tablinks" onclick="openMenu('one')">
                            <img src="/assets/images/product/icon/01.png" alt="food-product">
                            <span>Burger Menu</span>
                        </li>
                        <li class="tablinks" id="defaultOpen" onclick="openMenu('two')">
                            <img src="/assets/images/product/icon/02.png" alt="food-product">
                            <span>fizza menu</span>
                        </li>
                        <li class="tablinks" onclick="openMenu('three')">
                            <img src="/assets/images/product/icon/03.png" alt="food-product">
                            <span>snaks Menu</span>
                        </li>
                        <li class="tablinks" onclick="openMenu('four')">
                            <img src="/assets/images/product/icon/04.png" alt="food-product">
                            <span>drinks Menu</span>
                        </li>
                    </ul>
                    <div id="one" class="tabcontent">
                        <div class="product">
                            <div class="section-wrapper">
                                <div class="row no-gutters">
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="two" class="tabcontent">
                        <div class="product">
                            <div class="section-wrapper">
                                <div class="row no-gutters">
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="three" class="tabcontent">
                        <div class="product">
                            <div class="section-wrapper">
                                <div class="row no-gutters">
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="four" class="tabcontent">
                        <div class="product">
                            <div class="section-wrapper">
                                <div class="row no-gutters">
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/01.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/02.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/03.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6 col-12">
                                        <div class="product-item">
                                            <div class="product-thumb">
                                                <img src="/assets/images/product/04.jpg" alt="food-product">
                                                <span class="price">$20</span>
                                            </div>
                                            <div class="product-content">
                                                <p>Mexican Food</p>
                                                <h6><a href="#">Cream Chicken Chiladas</a></h6>
                                                <div class="rating">
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
                                                    <i class="icofont-star"></i>
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
        </section>

</template>

<script>

    export default {
        data() {
          return {
              data: {},
          }
        },
        props: ['restaurant'],
        mounted() {
            console.log('Mounted Restaurant Detailed Component')
        },
        computed: {
          
        },
        methods: {
            fetchData: function() {
              var self = this;
              axios.get('/api/restaurants').then(function (response) {
                  self.data = response.data;
              })
              .catch(function (error) {
                  console.log(error);
              });
            },
            openMenu: function(name) {
                var i, tabcontent, tablinks;
                tabcontent = document.getElementsByClassName("tabcontent");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }
                document.getElementById(name).style.display = "block";
                $('#'+name).addClass += " active";
            }
        }
    }

    

</script>
