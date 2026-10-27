<template>
    <section class="public-menu-list">
        <ul>
            <li><a href="/be-partner" :class="{ active: isActive('/be-partner') }">Be our partner</a></li>
            <li><a href="/restaurants" :class="{ active: isActive('/restaurants') }">Restaurants</a></li>
            <li><a href="/flowerstore" :class="{ active: isActive('/flowerstore') }">Flower store</a></li>
            <li><a href="/request/booking" :class="{ active: isActive('/request/booking') }">Delivery rates</a></li>
        </ul>
    </section>
</template>

<script>


    export default {
        data() {
            return {
                isLoggedIn: false,
            }
        },
        mounted() {

            this.isLoggedIn = isLogged;

            console.log("Register Form Mouted");
        },
        methods: {
            isActive: function(path) {
                return window.location.pathname.indexOf(path) === 0;
            },
            popRegister: function() {
                $('#myModalRegister').modal();
            }
        }
    }
</script>
