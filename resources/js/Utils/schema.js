/**
 * Schema.org JSON-LD generators for structured data.
 * 
 * These schemas help search engines understand content better
 * and can result in rich snippets in search results.
 */

/**
 * Organization schema for the main website.
 */
export function organizationSchema() {
    return {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: 'EDULIFE',
        url: 'https://edulife.uz',
        logo: 'https://edulife.uz/images/logo.png',
        description: 'O\'zbekistondagi eng yaxshi online ta\'lim platformasi',
        sameAs: [
            'https://facebook.com/edulife.uz',
            'https://instagram.com/edulife.uz',
            'https://t.me/edulife_uz',
            'https://youtube.com/@edulife_uz',
        ],
        contactPoint: {
            '@type': 'ContactPoint',
            telephone: '+998-90-123-45-67',
            contactType: 'customer service',
            availableLanguage: ['uz', 'ru'],
        },
        address: {
            '@type': 'PostalAddress',
            addressLocality: 'Tashkent',
            addressCountry: 'UZ',
        },
    };
}

/**
 * Course schema for individual course pages.
 */
export function courseSchema(course) {
    return {
        '@context': 'https://schema.org',
        '@type': 'Course',
        name: course.title,
        description: course.short_description || course.description?.substring(0, 500),
        url: `https://edulife.uz/courses/${course.slug}`,
        image: course.thumbnail_url,
        provider: {
            '@type': 'Organization',
            name: 'EDULIFE',
            url: 'https://edulife.uz',
        },
        instructor: course.teacher ? {
            '@type': 'Person',
            name: course.teacher.full_name,
            image: course.teacher.avatar_url,
            jobTitle: course.teacher.headline || 'O\'qituvchi',
        } : undefined,
        aggregateRating: course.reviews_count > 0 ? {
            '@type': 'AggregateRating',
            ratingValue: course.reviews_avg_rating || 0,
            reviewCount: course.reviews_count,
            bestRating: 5,
            worstRating: 1,
        } : undefined,
        offers: {
            '@type': 'Offer',
            price: course.is_free ? 0 : course.price,
            priceCurrency: 'UZS',
            availability: 'https://schema.org/InStock',
            url: `https://edulife.uz/courses/${course.slug}`,
        },
        coursePrerequisites: course.requirements || undefined,
        educationalLevel: course.level,
        inLanguage: course.language || 'uz',
        numberOfCredits: course.duration_hours,
        hasCourseInstance: {
            '@type': 'CourseInstance',
            courseMode: 'online',
            courseWorkload: `PT${course.duration_hours}H`,
        },
    };
}

/**
 * BreadcrumbList schema for navigation.
 */
export function breadcrumbSchema(items) {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: items.map((item, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            name: item.name,
            item: item.url || undefined,
        })),
    };
}

/**
 * WebPage schema for general pages.
 */
export function webPageSchema(page) {
    return {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: page.title,
        description: page.description,
        url: page.url,
        isPartOf: {
            '@type': 'WebSite',
            name: 'EDULIFE',
            url: 'https://edulife.uz',
        },
        inLanguage: 'uz',
        datePublished: page.publishedAt,
        dateModified: page.updatedAt,
    };
}

/**
 * FAQPage schema for FAQ sections.
 */
export function faqSchema(questions) {
    return {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: questions.map(q => ({
            '@type': 'Question',
            name: q.question,
            acceptedAnswer: {
                '@type': 'Answer',
                text: q.answer,
            },
        })),
    };
}

/**
 * Review schema for course reviews.
 */
export function reviewSchema(review, course) {
    return {
        '@context': 'https://schema.org',
        '@type': 'Review',
        itemReviewed: {
            '@type': 'Course',
            name: course.title,
        },
        author: {
            '@type': 'Person',
            name: review.user?.full_name || 'Foydalanuvchi',
        },
        reviewRating: {
            '@type': 'Rating',
            ratingValue: review.rating,
            bestRating: 5,
            worstRating: 1,
        },
        reviewBody: review.comment,
        datePublished: review.created_at,
    };
}

/**
 * VideoObject schema for video lessons.
 */
export function videoSchema(video) {
    return {
        '@context': 'https://schema.org',
        '@type': 'VideoObject',
        name: video.title,
        description: video.description,
        thumbnailUrl: video.thumbnail,
        uploadDate: video.created_at,
        duration: `PT${Math.floor(video.duration / 60)}M${video.duration % 60}S`,
        contentUrl: video.url,
        embedUrl: video.embed_url,
        interactionStatistic: {
            '@type': 'InteractionCounter',
            interactionType: 'https://schema.org/WatchAction',
            userInteractionCount: video.views || 0,
        },
    };
}

export default {
    organizationSchema,
    courseSchema,
    breadcrumbSchema,
    webPageSchema,
    faqSchema,
    reviewSchema,
    videoSchema,
};
