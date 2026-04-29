<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Cine_Sala_Estrella
 */

?>

	<footer class="site-footer footer" role="contentinfo">
		<div class="footer__inner">
			<div class="footer__grid">

				<!-- Columna marca -->
				<?php dynamic_sidebar('footer_1'); ?>

				<!-- Columna sedes -->
				 <?php dynamic_sidebar('footer_2'); ?>

				<!-- Columna links -->
				<?php dynamic_sidebar('footer_3'); ?>

			</div>

			<?php dynamic_sidebar('footer_4'); ?>

		</div>
	</footer><!-- #footer -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
