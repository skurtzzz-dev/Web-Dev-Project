<?php
/**
 * FilipinOnly sample menu data.
 * Replace or extend these records when your group connects a database.
 */
return [
    [
        'id' => 'tapsilog', 'name' => 'Classic Tapsilog', 'description' => 'Marinated beef tapa, garlic rice, and a sunny-side egg.',
        'price' => 189, 'category' => 'Almusal', 'tag' => 'Bestseller', 'prep' => '20–25 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Tapsilog.jpg/960px-Tapsilog.jpg',
        'allergens' => ['Egg', 'Soy', 'Wheat'], 'customs' => ['Remove egg', 'Less oil', 'Extra garlic'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'longsilog', 'name' => 'Longsilog', 'description' => 'Sweet Filipino longganisa with garlic rice and egg.',
        'price' => 169, 'category' => 'Almusal', 'tag' => 'Pinoy classic', 'prep' => '18–22 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/42/Longsilog.jpg/960px-Longsilog.jpg',
        'allergens' => ['Egg', 'Soy'], 'customs' => ['Remove egg', 'Less sweet', 'Extra vinegar'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'sinigang', 'name' => 'Sinigang na Baboy', 'description' => 'Pork belly in a comforting, tangy tamarind broth with vegetables.',
        'price' => 229, 'category' => 'Tanghalian', 'tag' => 'Comfort food', 'prep' => '30–35 min', 'serving' => '1 bowl',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/Sinigang_na_Baboy_DSCF4234.jpg/960px-Sinigang_na_Baboy_DSCF4234.jpg',
        'allergens' => ['Fish (optional bagoong)'], 'customs' => ['No kangkong', 'Extra gabi', 'Less sour'], 'rice' => true, 'spice' => true,
    ],
    [
        'id' => 'karekare', 'name' => 'Kare-Kare', 'description' => 'Slow-cooked oxtail and vegetables in rich peanut sauce, with bagoong on the side.',
        'price' => 269, 'category' => 'Tanghalian', 'tag' => 'House favorite', 'prep' => '35–40 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/Mac_MG_5939.jpg/960px-Mac_MG_5939.jpg',
        'allergens' => ['Peanuts', 'Fish (bagoong)'], 'customs' => ['Bagoong on the side', 'Extra vegetables', 'Less sauce'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'inasal', 'name' => 'Chicken Inasal', 'description' => 'Grilled Bacolod-style chicken brushed with annatto oil and calamansi.',
        'price' => 219, 'category' => 'Hapunan', 'tag' => 'Grilled fresh', 'prep' => '25–30 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Chicken_Inasal_%2B_Rice%2C_Jun_2024.jpg/960px-Chicken_Inasal_%2B_Rice%2C_Jun_2024.jpg',
        'allergens' => ['Soy'], 'customs' => ['No chicken oil', 'Extra calamansi', 'Well done'], 'rice' => true, 'spice' => true,
    ],
    [
        'id' => 'palabok', 'name' => 'Pancit Palabok', 'description' => 'Rice noodles with savory shrimp sauce, crushed chicharon, and calamansi.',
        'price' => 199, 'category' => 'Hapunan', 'tag' => 'Crowd pleaser', 'prep' => '20–25 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/Pancit_palabok_with_calamansi_%28cropped%29.jpg/960px-Pancit_palabok_with_calamansi_%28cropped%29.jpg',
        'allergens' => ['Shellfish', 'Egg', 'Wheat'], 'customs' => ['No chicharon', 'Calamansi on the side', 'Extra sauce'], 'rice' => false, 'spice' => true,
    ],
    [
        'id' => 'halohalo', 'name' => 'Classic Halo-Halo', 'description' => 'A cool mix of crushed ice, sweet beans, jellies, leche flan, and ube.',
        'price' => 139, 'category' => 'Panghimagas', 'tag' => 'Sweet treat', 'prep' => '8–12 min', 'serving' => '1 tall glass',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Halo_Halo.jpg/960px-Halo_Halo.jpg',
        'allergens' => ['Milk', 'Egg'], 'customs' => ['No leche flan', 'Less sweet', 'Extra ube'], 'rice' => false, 'spice' => false,
    ],
    [
        'id' => 'lecheflan', 'name' => 'Leche Flan', 'description' => 'Silky Filipino custard topped with a golden caramel layer.',
        'price' => 99, 'category' => 'Panghimagas', 'tag' => 'Made with care', 'prep' => '5–8 min', 'serving' => '1 slice',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/Leche_flan_Filipinas.jpg/960px-Leche_flan_Filipinas.jpg',
        'allergens' => ['Milk', 'Egg'], 'customs' => ['Extra caramel'], 'rice' => false, 'spice' => false,
    ],
    [
        'id' => 'arrozcaldo', 'name' => 'Arroz Caldo', 'description' => 'Chicken rice porridge simmered with ginger, garlic, and calamansi.',
        'price' => 159, 'category' => 'Almusal', 'tag' => 'Comfort bowl', 'prep' => '20–25 min', 'serving' => '1 bowl',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/Arroz_Caldo.jpg/960px-Arroz_Caldo.jpg',
        'allergens' => ['Fish (fish sauce)'], 'customs' => ['No scallions', 'Extra ginger', 'Less fish sauce'], 'rice' => false, 'spice' => false,
    ],
    [
        'id' => 'tortangtalong', 'name' => 'Tortang Talong', 'description' => 'Grilled eggplant omelette with egg and a little ground pork.',
        'price' => 149, 'category' => 'Almusal', 'tag' => 'Morning favorite', 'prep' => '15–20 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/EGGPLANT_TORTA.jpg/960px-EGGPLANT_TORTA.jpg',
        'allergens' => ['Egg', 'Soy'], 'customs' => ['No pork', 'Extra egg', 'Well done'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'pandesal', 'name' => 'Pandesal at Mantikilya', 'description' => 'Warm Filipino salted bread rolls served with butter.',
        'price' => 79, 'category' => 'Almusal', 'tag' => 'Morning staple', 'prep' => '5–8 min', 'serving' => '2 rolls',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/48/Pinoy_Pandesal.jpg/960px-Pinoy_Pandesal.jpg',
        'allergens' => ['Wheat', 'Milk', 'Egg'], 'customs' => ['Extra butter', 'Toasted', 'No butter'], 'rice' => false, 'spice' => false,
    ],
    [
        'id' => 'adobo', 'name' => 'Chicken Adobo', 'description' => 'Chicken braised in soy sauce, vinegar, garlic, and bay leaf.',
        'price' => 209, 'category' => 'Tanghalian', 'tag' => 'Pinoy classic', 'prep' => '25–30 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c9/Adobo_DSCF4391.jpg/960px-Adobo_DSCF4391.jpg',
        'allergens' => ['Soy'], 'customs' => ['Extra sauce', 'Less vinegar', 'Extra garlic'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'tinola', 'name' => 'Chicken Tinola', 'description' => 'Ginger chicken soup with sayote, green papaya, and chili leaves.',
        'price' => 199, 'category' => 'Tanghalian', 'tag' => 'Soupy classic', 'prep' => '25–30 min', 'serving' => '1 bowl',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/62/Chicken_tinola.jpg/960px-Chicken_tinola.jpg',
        'allergens' => ['Fish (fish sauce)'], 'customs' => ['No chili leaves', 'Extra sayote', 'Less ginger'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'laing', 'name' => 'Laing', 'description' => 'Taro leaves slow-cooked in coconut milk with chili and shrimp paste.',
        'price' => 189, 'category' => 'Tanghalian', 'tag' => 'Bicolano classic', 'prep' => '30–35 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/59/Laing%2C_Bicolano_dish.jpg/960px-Laing%2C_Bicolano_dish.jpg',
        'allergens' => ['Shellfish (shrimp paste)'], 'customs' => ['No chili', 'Extra coconut milk', 'Shrimp paste on the side'], 'rice' => true, 'spice' => true,
    ],
    [
        'id' => 'lechonkawali', 'name' => 'Lechon Kawali', 'description' => 'Deep-fried pork belly served with toyomansi or liver sauce.',
        'price' => 249, 'category' => 'Hapunan', 'tag' => 'Crispy favorite', 'prep' => '30–35 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cc/Lechon_Kawali.jpg/960px-Lechon_Kawali.jpg',
        'allergens' => ['Soy'], 'customs' => ['Extra crispy', 'Sauce on the side', 'No sauce'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'bicolexpress', 'name' => 'Bicol Express', 'description' => 'Pork and long chilies simmered in creamy coconut milk with shrimp paste.',
        'price' => 229, 'category' => 'Hapunan', 'tag' => 'Spicy favorite', 'prep' => '25–30 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/Bicol_Express.jpg/960px-Bicol_Express.jpg',
        'allergens' => ['Shellfish (shrimp paste)'], 'customs' => ['Less spicy', 'Extra sauce', 'No shrimp paste'], 'rice' => true, 'spice' => true,
    ],
    [
        'id' => 'pinakbet', 'name' => 'Pinakbet', 'description' => 'Mixed vegetables with tomato, pork, and bagoong.',
        'price' => 189, 'category' => 'Hapunan', 'tag' => 'Farmhouse plate', 'prep' => '20–25 min', 'serving' => '1 plate',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cf/Pinakbet_ni_Lakay_Jesse.jpg/960px-Pinakbet_ni_Lakay_Jesse.jpg',
        'allergens' => ['Shellfish (bagoong)'], 'customs' => ['No pork', 'Less bagoong', 'Extra vegetables'], 'rice' => true, 'spice' => false,
    ],
    [
        'id' => 'ubehalaya', 'name' => 'Ube Halaya', 'description' => 'Mashed purple yam cooked with coconut milk and sugar, topped with latik.',
        'price' => 119, 'category' => 'Panghimagas', 'tag' => 'Purple classic', 'prep' => '5–8 min', 'serving' => '1 cup',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/ba/Ube_halaya%2C_Filipino_dessert.jpg/960px-Ube_halaya%2C_Filipino_dessert.jpg',
        'allergens' => ['Milk'], 'customs' => ['Extra latik', 'Less sweet', 'No latik'], 'rice' => false, 'spice' => false,
    ],
    [
        'id' => 'turon', 'name' => 'Turon', 'description' => 'Banana and jackfruit spring rolls caramelized with sugar and fried until crisp.',
        'price' => 109, 'category' => 'Panghimagas', 'tag' => 'Crunchy sweet', 'prep' => '12–15 min', 'serving' => '2 pieces',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Turon_na_Saging.jpg/960px-Turon_na_Saging.jpg',
        'allergens' => ['Wheat', 'Egg'], 'customs' => ['No jackfruit', 'Extra sugar', 'Less sugar'], 'rice' => false, 'spice' => false,
    ],
    [
        'id' => 'sapinsapin', 'name' => 'Sapin-Sapin', 'description' => 'Layered glutinous rice cake with coconut, ube, and latik.',
        'price' => 129, 'category' => 'Panghimagas', 'tag' => 'Layered treat', 'prep' => '5–8 min', 'serving' => '1 slice',
        'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/51/Sapin-sapin.jpg/960px-Sapin-sapin.jpg',
        'allergens' => ['Milk'], 'customs' => ['Extra latik', 'Less sweet', 'No latik'], 'rice' => false, 'spice' => false,
    ],
];
