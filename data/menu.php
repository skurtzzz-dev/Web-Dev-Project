<?php
/**
 * FilipinOnly sample menu data.
 * Replace or extend these records when your group connects a database.
 */
return [
    [
        'id' => 'tapsilog', 'name' => 'Classic Tapsilog', 'description' => 'Marinated beef tapa, garlic rice, and a sunny-side egg.',
        'price' => 189, 'category' => 'Almusal', 'tag' => 'Bestseller', 'rating' => '4.9', 'prep' => '20–25 min',
        'image' => 'https://images.unsplash.com/photo-1516684732162-798a0062be99?auto=format&fit=crop&w=900&q=85',
        'allergens' => ['Egg', 'Soy', 'Wheat'], 'customs' => ['Remove egg', 'Less oil', 'Extra garlic'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'longsilog', 'name' => 'Longsilog', 'description' => 'Sweet Filipino longganisa with garlic rice and egg.',
        'price' => 169, 'category' => 'Almusal', 'tag' => 'Pinoy classic', 'rating' => '4.8', 'prep' => '18–22 min',
        'image' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=900&q=85',
        'allergens' => ['Egg', 'Soy'], 'customs' => ['Remove egg', 'Less sweet', 'Extra vinegar'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'sinigang', 'name' => 'Sinigang na Baboy', 'description' => 'Pork belly in a comforting, tangy tamarind broth with vegetables.',
        'price' => 229, 'category' => 'Tanghalian', 'tag' => 'Comfort food', 'rating' => '4.9', 'prep' => '30–35 min',
        'image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85',
        'allergens' => ['Fish (optional bagoong)'], 'customs' => ['No kangkong', 'Extra gabi', 'Less sour'], 'rice' => true, 'spice' => true,
    ],
    [
        'id' => 'karekare', 'name' => 'Kare-Kare', 'description' => 'Slow-cooked oxtail and vegetables in rich peanut sauce, with bagoong on the side.',
        'price' => 269, 'category' => 'Tanghalian', 'tag' => 'House favorite', 'rating' => '5.0', 'prep' => '35–40 min',
        'image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85',
        'allergens' => ['Peanuts', 'Fish (bagoong)'], 'customs' => ['Bagoong on the side', 'Extra vegetables', 'Less sauce'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'inasal', 'name' => 'Chicken Inasal', 'description' => 'Grilled Bacolod-style chicken brushed with annatto oil and calamansi.',
        'price' => 219, 'category' => 'Hapunan', 'tag' => 'Grilled fresh', 'rating' => '4.9', 'prep' => '25–30 min',
        'image' => 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?auto=format&fit=crop&w=900&q=85',
        'allergens' => ['Soy'], 'customs' => ['No chicken oil', 'Extra calamansi', 'Well done'], 'rice' => true, 'spice' => true,
    ],
    [
        'id' => 'palabok', 'name' => 'Pancit Palabok', 'description' => 'Rice noodles with savory shrimp sauce, crushed chicharon, and calamansi.',
        'price' => 199, 'category' => 'Hapunan', 'tag' => 'Crowd pleaser', 'rating' => '4.8', 'prep' => '20–25 min',
        'image' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=900&q=85',
        'allergens' => ['Shellfish', 'Egg', 'Wheat'], 'customs' => ['No chicharon', 'Calamansi on the side', 'Extra sauce'], 'rice' => false, 'spice' => true,
    ],
    [
        'id' => 'halohalo', 'name' => 'Classic Halo-Halo', 'description' => 'A cool mix of crushed ice, sweet beans, jellies, leche flan, and ube.',
        'price' => 139, 'category' => 'Panghimagas', 'tag' => 'Sweet treat', 'rating' => '4.9', 'prep' => '8–12 min',
        'image' => 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=900&q=85',
        'allergens' => ['Milk', 'Egg'], 'customs' => ['No leche flan', 'Less sweet', 'Extra ube'], 'rice' => false, 'spice' => false,
    ],
    [
        'id' => 'lecheflan', 'name' => 'Leche Flan', 'description' => 'Silky Filipino custard topped with a golden caramel layer.',
        'price' => 99, 'category' => 'Panghimagas', 'tag' => 'Made with care', 'rating' => '4.9', 'prep' => '5–8 min',
        'image' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=900&q=85',
        'allergens' => ['Milk', 'Egg'], 'customs' => ['Extra caramel'], 'rice' => false, 'spice' => false,
    ],
];
