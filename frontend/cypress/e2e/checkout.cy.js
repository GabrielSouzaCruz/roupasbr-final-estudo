describe('E-commerce Flow', () => {
    beforeEach(() => {
        cy.visit('http://localhost:3000');
    });

    it('completes full checkout with Pix', () => {
        // Login
        cy.visit('/login');
        cy.get('input[type="email"]').type('test@example.com');
        cy.get('input[type="password"]').type('password123');
        cy.get('button[type="submit"]').click();
        
        // Add to cart
        cy.visit('/produto/camiseta-basica-preta');
        cy.contains('Adicionar ao Carrinho').click();
        
        // Checkout
        cy.visit('/carrinho');
        cy.contains('Finalizar Compra').click();
        
        // Select Pix
        cy.get('input[value="pix"]').check();
        cy.contains('Pagar').click();
        
        // Verify order created
        cy.url().should('include', '/pedido/');
        cy.contains('Pedido criado com sucesso');
    });

    it('abandoned cart triggers email', () => {
        // Login and add to cart
        cy.visit('/login');
        // ... add items
        
        // Leave page
        cy.visit('/');
        
        // Check email sent (via Mailtrap or similar)
        // This requires email testing service integration
    });

    it('applies coupon code', () => {
        // Add items to cart
        // Enter coupon code
        // Verify discount applied
    });
});
