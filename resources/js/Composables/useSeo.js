import { usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

/**
 * SEO Composable for Vue components.
 * 
 * Provides easy-to-use SEO meta tag management including
 * title, description, Open Graph, Twitter Cards, and canonical URLs.
 * 
 * @example
 * const { seoMeta } = useSeo({
 *     title: 'Python Kursi',
 *     description: 'Python dasturlash tilini o\'rganing',
 *     image: '/images/python-course.jpg',
 *     type: 'article'
 * });
 */
export function useSeo(options = {}) {
    const page = usePage();
    
    // Default values
    const defaults = {
        siteName: 'EDULIFE',
        baseUrl: 'https://edulife.uz',
        defaultTitle: 'EDULIFE - Online Ta\'lim Platformasi',
        defaultDescription: 'O\'zbekistondagi eng yaxshi online ta\'lim platformasi. 1000+ kurs, professional o\'qituvchilar, sertifikatlar.',
        defaultImage: '/images/og-default.jpg',
        twitterHandle: '@edulife_uz',
        locale: 'uz_UZ',
    };
    
    // Computed values
    const title = computed(() => {
        if (options.title) {
            return `${options.title} | ${defaults.siteName}`;
        }
        return defaults.defaultTitle;
    });
    
    const description = computed(() => options.description || defaults.defaultDescription);
    
    const image = computed(() => {
        const img = options.image || defaults.defaultImage;
        // Make absolute URL if relative
        if (img.startsWith('/')) {
            return defaults.baseUrl + img;
        }
        return img;
    });
    
    const url = computed(() => defaults.baseUrl + page.url);
    
    const type = computed(() => options.type || 'website');
    
    // Generate meta tags object for <Head>
    const seoMeta = computed(() => ({
        // Basic
        title: title.value,
        description: description.value,
        
        // Open Graph
        ogTitle: title.value,
        ogDescription: description.value,
        ogImage: image.value,
        ogUrl: url.value,
        ogType: type.value,
        ogSiteName: defaults.siteName,
        ogLocale: defaults.locale,
        
        // Twitter Card
        twitterCard: 'summary_large_image',
        twitterTitle: title.value,
        twitterDescription: description.value,
        twitterImage: image.value,
        twitterSite: defaults.twitterHandle,
        
        // Canonical
        canonical: url.value,
    }));
    
    return {
        title,
        description,
        image,
        url,
        type,
        seoMeta,
        defaults,
    };
}

/**
 * Generate Course-specific SEO meta.
 */
export function useCourseSeo(course) {
    return useSeo({
        title: course.title,
        description: course.short_description || course.description?.substring(0, 160),
        image: course.thumbnail_url,
        type: 'article',
    });
}

/**
 * Generate Category/Direction SEO meta.
 */
export function useDirectionSeo(direction) {
    return useSeo({
        title: `${direction.name} Kurslari`,
        description: direction.description || `${direction.name} yo'nalishi bo'yicha eng yaxshi kurslar`,
        image: direction.image_url,
    });
}

/**
 * Generate Teacher profile SEO meta.
 */
export function useTeacherSeo(teacher) {
    return useSeo({
        title: `${teacher.full_name} - O'qituvchi`,
        description: teacher.bio || `${teacher.full_name} - EDULIFE platformasida professional o'qituvchi`,
        image: teacher.avatar_url,
        type: 'profile',
    });
}

export default useSeo;
